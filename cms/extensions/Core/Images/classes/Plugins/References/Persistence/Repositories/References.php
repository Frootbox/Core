<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories;

/**
 *
 */
class References extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $class = \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference::class;
}