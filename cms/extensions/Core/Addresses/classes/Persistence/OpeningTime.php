<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Persistence;

class OpeningTime extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Visibility;

    protected $model = Repositories\OpeningTimes::class;

    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }
}
