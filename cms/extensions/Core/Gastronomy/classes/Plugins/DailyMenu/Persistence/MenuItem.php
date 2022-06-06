<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence;

class MenuItem extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\MenuItems::class;

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
