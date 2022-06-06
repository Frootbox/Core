<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence;

class Link extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\Links::class;

    /**
     * Generate alias
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}