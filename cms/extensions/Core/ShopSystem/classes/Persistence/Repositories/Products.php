<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

/**
 *
 */
class Products extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $table = 'shop_products';
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class;
}
