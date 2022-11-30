<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\PaymentMethods\Paypal;

class PaymentMethod extends \Frootbox\Ext\Core\Events\Plugins\Booking\PaymentMethods\AbstractPaymentMethod
{
    protected ?\Frootbox\Config\Config $config = null;

    /**
     *
     */
    public function __construct(\Frootbox\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     *
     */
    public function postPaymentSelectionAction()
    {
        if (empty($paypalConfig = $this->config->get('Plugins.Core.Events.Booking.Paypal'))) {
            throw new \Exception('PaypalConfigMissing');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $paypalConfig->clientId . ":" . $paypalConfig->secret);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output, true);

        $_SESSION['events']['booking']['payment']['data'] = $response;

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        "currency_code" => "EUR",
                        "value" => $_SESSION['events']['booking']['event']['total'],
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => SERVER_PATH_PROTOCOL . 'static/Ext/Core/Events/Paypal/landingpage?' . SID,
            ],
        ];

        $payload = json_encode($payload, JSON_HEX_QUOT | JSON_HEX_TAG);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['events']['booking']['payment']['data']['access_token'],
            'Content-Type: application/json',
        ]);

        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output, true);

        $_SESSION['events']['booking']['payment']['transaction'] = $result;

        foreach ($result['links'] as $link) {

            if ($link['rel'] == 'approve') {

                return [
                    'redirect' => $link['href']
                ];
            }
        }

        throw new \Exception('Missing Redirect Link');
    }

    /**
     *
     */
    public function preCheckoutAction(\Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking $booking): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com/v2/checkout/orders/' . $_SESSION['events']['booking']['payment']['transaction']['id'] . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['events']['booking']['payment']['data']['access_token'],
            'Content-Type: application/json'
        ]);

        $output = curl_exec($ch);
        $response = json_decode($output, true);

        if (!empty($response['name']) and $response['name'] == 'UNPROCESSABLE_ENTITY') {

            if ($response['details'][0]['issue'] != 'ORDER_ALREADY_CAPTURED') {
                throw new \Exception($response['details'][0]['issue']);
            }
        }

        curl_close($ch);

        unset($_SESSION['events']['booking']['payment']);

        $booking->addConfig([
            'payment' => [
                'state' => 'Payed',
            ],
        ]);
        $booking->save();

        return [
            'state' => 'Payed',
        ];
    }
}
