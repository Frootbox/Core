<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence;

class Field extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Fields::class;

    /**
     * Disable alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
