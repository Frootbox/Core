<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\Stripe;

use Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart;

class Method extends \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
{
    protected bool $forcesPaymentExtraStep = true;

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking
     * @return string|null
     */
    public function getTitleFinalForBooking(\Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking): ?string
    {
        return 'Core.ShopSystem.PaymentMethods.Stripe.PaymentMethod' . ucfirst($booking->getConfig('payment.transactionData.paymentMethod'));
    }

    public function onBeforeRenderInput(
        \Frootbox\Config\Config $configuration,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopCart,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): array
    {
        // Init stripe client
        $stripe = new \Stripe\StripeClient([
            'api_key' => $configuration->get('Stripe.Api.Secret'),
        ]);

        // Search existing customer
        $response = $stripe->customers->search([
            'query' => 'email:\'' . $shopCart->getPersonal('email') . '\'',
        ]);

        if ($response->count() == 1) {
            $customer = $response->first();
        } else {
            $customer = $stripe->customers->create([
                'name' => $shopCart->getPersonal('firstname') . ' ' . $shopCart->getPersonal('lastname'),
                'email' => $shopCart->getPersonal('email'),
            ]);
        }

        $paymentIntent = null;

        if (!empty($_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'])) {

            try {

                // Retrieve payment intent
                $paymentIntent = $stripe->paymentIntents->retrieve(
                    $_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'],
                    []
                );
            } catch (\Exception $exception) {

                // Unset old payment intent
                $paymentIntent = null;
                $_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'] = null;
            }
        }

        if ($paymentIntent and $paymentIntent->status == 'succeeded') {
            $paymentIntent = null;
            $_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'] = null;
        }

        if ($paymentIntent === null) {

            // Create new payment intent
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $shopCart->getTotal() * 100,
                'currency' => 'eur',
                'customer' => $customer->id,
                'capture_method' => 'automatic',
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            $_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'] = $paymentIntent->id;
        }

        if ($paymentIntent->amount != $shopCart->getTotal() * 100) {

            $paymentIntent = $stripe->paymentIntents->update($paymentIntent->id, [
                'amount' => $shopCart->getTotal() * 100,
            ]);
        }

        // Generate redirect url
        $checkOutPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\plugin::class,
            ]
        ]);

        $redirectUrl = $checkOutPlugin->getAjaxUri('checkout', [ 'preChecked' => true ], [
            'absolute' => true,
        ]);

        // Check payment state
        if (!empty($paymentIntent->amount) and $paymentIntent->amount <= $paymentIntent->amount_received) {
            ob_end_clean();
            header('Location: ' . $redirectUrl);
            exit;
        }

        return [
            'PublicKey' => $configuration->get('Stripe.Api.Key'),
            'ClientSecret' => $paymentIntent->client_secret,
            'ReturnUrl' => $redirectUrl,
        ];
    }

    public function onPostAuthed(
        \Frootbox\Http\Get $get,
    ): void
    {
        $_SESSION['cart']['paymentmethod']['stripe']['transaction'] = $get->getData();
    }

    public function preCheckoutAction(
        \Frootbox\Config\Config $configuration,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopCart,
    ): array
    {
        // Init stripe client
        $stripe = new \Stripe\StripeClient([
            'api_key' => $configuration->get('Stripe.Api.Secret'),
        ]);

        // Retrieve payment intent
        $paymentIntent = $stripe->paymentIntents->retrieve(
            $_SESSION['cart']['paymentmethod']['stripe']['paymentIntentId'],
            []
        );

        if (!empty($paymentIntent->amount) and $paymentIntent->amount > $paymentIntent->amount_received) {
            throw new \Exception('Payment failed.');
        }

        // Store order number to stripe
        $paymentIntent = $stripe->paymentIntents->update($paymentIntent->id, [
            'metadata' => [
                'orderNumber' => $shopCart->getOrderNumber(),
            ],
        ]);

        $paymentMethod = $stripe->paymentMethods->retrieve($paymentIntent->payment_method, []);

        return [
            'customerId' => $paymentIntent->customer,
            'paymentIntendId' => $paymentIntent->id,
            'paymentMethod' => $paymentMethod->type,
        ];
    }
}
