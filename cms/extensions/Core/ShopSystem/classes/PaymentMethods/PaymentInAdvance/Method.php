<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentInAdvance;

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
