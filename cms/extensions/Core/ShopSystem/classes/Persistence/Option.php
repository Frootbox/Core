<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Option extends \Frootbox\Persistence\AbstractConfigurableRow
{
    protected $model = Repositories\Option::class;
    protected $table = 'shop_products_options';

    /**
     *
     */
    public function delete()
    {
        $stocksRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock::class);

        // Cleanup stocks
        $sql = 'SELECT * FROM `shop_products_stocks` WHERE JSON_CONTAINS(groupData, \'{"' . $this->getGroupId() . '":"' . $this->getId() . '"}\');';

        $result = $stocksRepository->fetchByQuery($sql);

        foreach ($result as $stock) {
            $stock->delete();
        }

        return parent::delete();
    }

    /**
     *
     */
    public function getGroup(): \Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetOptionGroup
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup::class);

        return $repository->fetchById($this->getGroupId());
    }

    /**
     *
     */
    public function getProduct(): \Frootbox\Ext\Core\ShopSystem\Persistence\Product
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);

        return $repository->fetchById($this->getProductId());
    }

    /**
     *
     */
    public function getSurcharge(): float
    {
        return $this->data['surcharge'] / 100;
    }

    /**
     *
     */
    public function setSurcharge(float $surcharge): void
    {
        $this->data['surcharge'] = $surcharge * 100;
        $this->changed['surcharge'] = true;
    }
}
