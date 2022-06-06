<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Stock extends \Frootbox\Persistence\AbstractConfigurableRow
{
    protected $model = Repositories\Stock::class;
    protected $table = 'shop_products_stocks';

    /**
     *
     */
    public function getOptionsAsString(): string
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option::class);
        $groupData = json_decode($this->getGroupData(), true);
        $options = [];

        foreach ($groupData as $groupId => $optionId) {

            if (empty($optionId)) {
                continue;
            }

            // Fetch option
            $option = $repository->fetchById($optionId);
            $options[] = $option->getTitle();
        }

        return implode(', ', $options);
    }

    /**
     *
     */
    public function getPrice(): float
    {
        return $this->data['price'] / 100;
    }
}
