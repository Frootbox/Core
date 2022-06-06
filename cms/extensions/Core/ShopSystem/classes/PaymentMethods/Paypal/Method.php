<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\Paypal;

class Method extends \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
{
    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function postPaymentSelectionAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    )
    {
        if (empty($paypalConfig = $config->get('shop.payment.paypal'))) {
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

        $_SESSION['cart']['paymentmethod']['data'] = $response;

        $return = !empty($config->get('shop.payment.paypal.redirect')) ? urlencode($config->get('shop.payment.paypal.redirect') . '?' . SID) : null;

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        "currency_code" => "EUR",
                        "value" => $shopcart->getTotal(),
                    ],
                ],
            ],
            'application_context' => [
                'return_url' => SERVER_PATH_PROTOCOL . 'static/Ext/Core/ShopSystem/Paypal/landingpage?' . SID . '&return=' . $return . '&returnPluginId=' . $config->get('shop.payment.paypal.returnPluginId'),
            ],
        ];

        $payload = json_encode($payload, JSON_HEX_QUOT | JSON_HEX_TAG);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['cart']['paymentmethod']['data']['access_token'],
            'Content-Type: application/json',
        ]);

        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output, true);

        $_SESSION['cart']['paymentmethod']['data']['transaction'] = $result;

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
    public function preCheckoutAction(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): void
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.paypal.com/v2/checkout/orders/' . $_SESSION['cart']['paymentmethod']['data']['transaction']['id'] . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['cart']['paymentmethod']['data']['access_token'],
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

        $shopcart->setPayed();
    }
}
