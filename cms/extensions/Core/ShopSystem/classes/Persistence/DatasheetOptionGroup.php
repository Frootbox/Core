<?php
/**
 * @author Jan Habbo Brüning
 * @date 2021-12-05
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class DatasheetOptionGroup extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\DatasheetOptionGroup::class;

    /**
     *
     */
    public function delete()
    {
        // Fetch options
        $optionsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option::class);
        $stocksRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock::class);

        $result = $optionsRepository->fetch([
            'where' => [
                'groupId' => $this->getId(),
            ],
        ]);

        foreach ($result as $option) {

            // Cleanup stocks
            $sql = 'SELECT * FROM `shop_products_stocks` WHERE JSON_CONTAINS(groupData, \'{"' . $this->getId() . '":"' . $option->getId() . '"}\');';

            $result = $stocksRepository->fetchByQuery($sql);

            foreach ($result as $stock) {
                $stock->delete();
            }

            $option->delete();
        }

        return parent::delete();
    }

    /**
     *
     */
    public function getDatasheet(): \Frootbox\Ext\Core\ShopSystem\Persistence\Datasheet
    {
        // Fetch datasheet
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets::class);
        $datasheet = $repository->fetchById($this->getParentId());

        return $datasheet;
    }

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getOptionsForProduct(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Product $product
    ): \Frootbox\Db\Result
    {
        // Fetch options
        $optionsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option::class);

        $result = $optionsRepository->fetch([
            'where' => [
                'productId' => $product->getId(),
                'groupId' => $this->getId(),
            ],
        ]);

        foreach ($result as $option) {

        }

        return $result;
    }
}
