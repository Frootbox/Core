<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class SelfPickupDay extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\SelfPickupDay::class;

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
