<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class PickupLocation extends \Frootbox\Ext\Core\Addresses\Persistence\Address
{
    protected $model = Repositories\Addresses::class;

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
