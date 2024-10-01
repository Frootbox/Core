<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\StripeIdeal;

use Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart;

class Method extends \Frootbox\Ext\Core\ShopSystem\PaymentMethods\StripeCard\Method
{
    protected bool $isForcingNewPaymentFlow = true;
    protected bool $hasCheckoutControl = true;

    protected string $stripePaymentMethodType = 'ideal';

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

}
