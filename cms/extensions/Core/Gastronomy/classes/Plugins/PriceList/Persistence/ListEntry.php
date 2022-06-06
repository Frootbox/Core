<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class ListEntry extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\ListEntries::class;

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
    public function getNumber(): ?string
    {
        return $this->getConfig('number');
    }

    /**
     *
     */
    public function getPrices(): \Frootbox\Db\Result
    {
        // Fetch prices
        $pricesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices::class);
        $result = $pricesRepository->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function setNumber(string $number): void
    {
        $this->addConfig([
            'number' => $number
        ]);
    }
}
