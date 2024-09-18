<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories;

/**
 *
 */
class Categories extends \Frootbox\Persistence\Repositories\Categories
{
    protected $class = \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Category::class;
}