<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories;

/**
 *
 */
class Bookings extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    protected $class = \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking::class;
}