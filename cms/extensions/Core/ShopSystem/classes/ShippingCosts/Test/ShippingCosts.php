<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\ShippingCosts\Test;

class ShippingCosts extends \Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts
{
    /**
     *
     */
    public function getCosts(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem $item,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): ?float
    {
        return 10;
    }
}
