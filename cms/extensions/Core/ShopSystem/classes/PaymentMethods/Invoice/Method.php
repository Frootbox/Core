<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\Invoice;

class Method extends \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
{
    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}
