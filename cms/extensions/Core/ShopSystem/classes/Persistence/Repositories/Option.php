<?php
/**
 * @author Jan Habbo Brüning
 * @date 2021-12-05
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence\Repositories;

/**
 *
 */
class Option extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    protected $table = 'shop_products_options';
    protected $class = \Frootbox\Ext\Core\ShopSystem\Persistence\Option::class;
}
