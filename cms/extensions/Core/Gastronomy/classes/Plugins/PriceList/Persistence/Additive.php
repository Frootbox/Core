<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class Additive extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Prices::class;

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
