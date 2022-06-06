<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class Price extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Prices::class;

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function hasAdditive(Additive $additive): bool
    {
        if (empty($this->getConfig('additives'))) {
            return false;
        }

        return in_array($additive->getId(), $this->getConfig('additives'));
    }
}
