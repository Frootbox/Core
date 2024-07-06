<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\StripeKlarna;

use Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart;

class Method extends \Frootbox\Ext\Core\ShopSystem\PaymentMethods\StripeCard\Method
{
    protected bool $isForcingNewPaymentFlow = true;
    protected bool $hasCheckoutControl = true;

    protected string $stripePaymentMethodType = 'klarna';

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

}
