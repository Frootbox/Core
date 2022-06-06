<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Variant extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Variants::class;

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
    public function getPrice(): float
    {
        return (float) $this->getConfig('price');
    }

    /**
     *
     */
    public function setPrice($price): void
    {
        $this->addConfig([
            'price' => $price
        ]);
    }
}
