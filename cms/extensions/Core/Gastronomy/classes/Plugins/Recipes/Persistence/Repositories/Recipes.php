<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories;

/**
 *
 */
class Recipes extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    protected $table = 'recipes';
    protected $class = \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Recipe::class;
}
