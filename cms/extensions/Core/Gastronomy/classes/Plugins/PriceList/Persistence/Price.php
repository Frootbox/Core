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
    public function getAdditives(): array
    {
        if (empty($this->getConfig('additives'))) {
            return [];
        }

        $additives = [];

        $additiveRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives::class);

        foreach ($this->getConfig('additives') as $additiveId) {
            $additives[] = $additiveRepository->fetchById($additiveId);
        }

        return $additives;
    }

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
    public function getPrice(): ?float
    {
        return $this->getConfig('price');
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
