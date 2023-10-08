<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\ShippingCosts\FixedCharge;

class ShippingCosts extends \Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getCosts(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem $item,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): ?float
    {
        return (float) $this->getConfig('charge');
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @return void
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'charge' => $post->get('charge'),
        ]);
        $this->save();
    }
}
