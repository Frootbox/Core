<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class Navigations extends \Frootbox\Db\Model
{
    protected $table = 'navigations';
    protected $class = \Frootbox\Persistence\Navigation::class;
}
