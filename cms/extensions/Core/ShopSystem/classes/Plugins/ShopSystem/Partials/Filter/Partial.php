<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Partials\Filter;

class Partial extends \Frootbox\View\Partials\AbstractPartial
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData $productsDataRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): void
    {
        if (!$this->hasAttribute('category')) {

            $category = $categoriesRepository->fetchOne([
                'where' => [
                    'parentId' => 0
                ]
            ]);
        }
        else {
            $category = $this->getAttribute('category');
        }

        $filters = $category->getFilters();
        $appliedFilters = $get->get('filter');


        foreach ($filters as $index => $filter) {

            $field = $filter['field'];

            $filters[$index]['aggregations'] = 0;

            // Build sql
            $sql = 'SELECT
                COUNT(DISTINCT x.itemId) as count,
                d.valueText
            FROM           
                shop_products i,    
                shop_products_data d,
                categories_2_items x,
                categories c
            WHERE
                i.id = x.itemId AND
                i.visibility >= ' . (IS_LOGGED_IN ? '1' : '2') . ' AND
                c.lft >= ' . $category->getLft() . ' AND
                c.rgt <= ' . $category->getRgt() . ' AND
                x.categoryId = c.id AND                
                x.itemId = d.productId AND 
                d.fieldId = ' . $field->getId() . ' AND
                d.valueText != ""
            GROUP BY                
                d.valueText';

            $result = $productsDataRepository->fetchByQuery($sql);

            $aggregations = $result->getData();

            if (!empty($appliedFilters)) {

                foreach ($appliedFilters as $appFilter) {

                    if ($appFilter['id'] != $field->getId()) {
                        continue;
                    }

                    foreach ($aggregations as $nindex => $agg) {

                        if ($agg['valueText'] == $appFilter['key']) {
                            $aggregations[$nindex]['active'] = true;

                            ++$filters[$index]['aggregations'];
                            break 2;
                        }
                    }
                }
            }

            $filters[$index]['aggregation'] = $aggregations;
        }

        $view->set('category', $category);
        $view->set('filters', $filters);
    }
}
