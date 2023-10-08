<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class ListEntry extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\ListEntries::class;

    /**
     * @return void
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function delete()
    {
        // Cleanup prices
        $this->getPrices()->map('delete');

        parent::delete();
    }

    /**
     * @return \Frootbox\Persistence\Alias|null
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->getConfig('number');
    }

    /**
     * @param array $parameters
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getPrices(array $parameters = []): \Frootbox\Db\Result
    {
        // Fetch prices
        $pricesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices::class);
        $result = $pricesRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        return $result;
    }

    /**
     * @param string $number
     * @return void
     */
    public function setNumber(string $number): void
    {
        $this->addConfig([
            'number' => $number
        ]);
    }
}
