<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class ProductData extends \Frootbox\Persistence\AbstractRow
{
    protected $table = 'shop_products_data';
    protected $model = Repositories\ProductsData::class;

    /**
     *
     */
    public function updateMetrics()
    {
        switch ($this->getType()) {

            default:
                $integer = (float) (str_replace(',', '.', $this->getValueText()));
        }

        $integer *= 1000;

        $this->setValueInt($integer);
    }
}
