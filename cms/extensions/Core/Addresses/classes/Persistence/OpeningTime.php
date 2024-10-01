<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Persistence;

class OpeningTime extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Visibility;

    protected $model = Repositories\OpeningTimes::class;

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    public function isClosed(): bool
    {
        return !empty($this->getConfig('isClosed'));
    }
}
