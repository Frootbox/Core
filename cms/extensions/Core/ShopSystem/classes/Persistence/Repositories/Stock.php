<?php
/**
 * @author Jan Habbo Brüning
 * @date 2021-12-05
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

/**
 *
 */
class Stock extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    protected $table = 'shop_products_stocks';
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\Stock::class;
}
