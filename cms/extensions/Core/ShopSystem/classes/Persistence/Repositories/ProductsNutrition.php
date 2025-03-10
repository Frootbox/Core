<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

/**
 *
 */
class ProductsNutrition extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    protected $table = 'shop_products_nutrition';
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\ProductNutrition::class;
}
