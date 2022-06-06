<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Events\Persistence\Repositories;

/**
 *
 */
class Events extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    protected $class = \Frootbox\Ext\Core\Events\Persistence\Event::class;

    use \Frootbox\Persistence\Repositories\Traits\Tags;
}