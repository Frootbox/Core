<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence;

class MenuTemplate extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\MenuTemplates::class;

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
