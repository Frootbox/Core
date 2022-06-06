<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

/**
 *
 */
class ProductsData extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    protected $table = 'shop_products_data';
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\ProductData::class;
}
