<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories;

/**
 *
 */
class Links extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $class = \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Link::class;
}
