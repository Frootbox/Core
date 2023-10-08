<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class ShippingCosts extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\ShippingCosts::class;

    protected bool $isApplicableToCertainProduct = false;
    protected string $title;

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
    public function isApplicableToCertainProduct(): bool
    {
        return $this->isApplicableToCertainProduct;
    }

    /**
     * @return bool
     */
    public function isShippingAddressValid(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    ): bool
    {
        return true;
    }

    /**
     *
     */
    public function getCosts(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem $item,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    ): ?float
    {
        return 0;
    }

    /**
     * @param $language
     * @return string|null
     */
    public function getTitle($language = null): ?string
    {
        if (!empty($this->data['title'])) {
            return $this->data['title'];
        }

        if (!empty($this->title)) {
            return $this->title;
        }

        preg_match('#\\\\([a-z]+)\\\\ShippingCosts$#i', get_class($this), $match);

        return $match[1];
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @return void
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {

    }
}
