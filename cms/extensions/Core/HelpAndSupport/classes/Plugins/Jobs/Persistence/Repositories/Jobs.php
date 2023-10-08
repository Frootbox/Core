<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories;

/**
 *
 */
class Jobs extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $class = \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job::class;
}
