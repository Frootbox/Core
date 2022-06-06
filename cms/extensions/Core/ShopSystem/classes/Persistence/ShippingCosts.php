<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class ShippingCosts extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\ShippingCosts::class;

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getCosts(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem $item,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): ?float
    {
        return 0;
    }

    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        preg_match('#\\\\([a-z]+)\\\\ShippingCosts$#i', get_class($this), $match);

        return $match[1];
    }
}
