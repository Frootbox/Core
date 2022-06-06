<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

class CategoriesConnections extends \Frootbox\Persistence\Repositories\CategoriesConnections
{
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\CategoryConnection::class;
    protected $itemClass = \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class;
    protected $categoryClass = \Frootbox\Ext\Core\ShopSystem\Persistence\Category::class;
}
