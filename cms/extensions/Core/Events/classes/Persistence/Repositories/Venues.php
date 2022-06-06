<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Events\Persistence\Repositories;

/**
 *
 */
class Venues extends \Frootbox\Persistence\Repositories\AbstractLocations
{
    protected $class = \Frootbox\Ext\Core\Events\Persistence\Venue::class;
}