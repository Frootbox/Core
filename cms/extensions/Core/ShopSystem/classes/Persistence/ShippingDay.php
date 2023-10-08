<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class ShippingDay extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\ShippingDay::class;

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
