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
     * @return ListEntry
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getListEntry(): \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\ListEntry
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries::class);
        $entry = $repository->fetchById($this->getParentId());

        return $entry;
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
