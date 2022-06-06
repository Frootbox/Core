<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Viewhelper;

class Products extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getProducts' => [
            'parameters',
        ],
    ];

    /**
     *
     */
    public function getProductsAction(
        array $parameters = null,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT 
            p.* 
        FROM
            shop_products p';

        if (!empty($parameters['categoryId'])) {
            $sql .= ',
             categories_2_items x
             ';
        }

        $sql .= ' WHERE 1 = 1 ';

        $payload = [];

        if (!empty($parameters['categoryId'])) {

            $payload['categoryClass'] = \Frootbox\Ext\Core\ShopSystem\Persistence\Category::class;
            $payload['categoryId'] = $parameters['categoryId'];
            $payload['itemClass'] = \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class;

            $sql .= ' AND x.categoryClass = :categoryClass
                AND x.categoryId = :categoryId
                AND x.itemClass = :itemClass 
                AND x.itemId = p.id';
        }

        if (!empty($parameters['pluginId'])) {
            $payload['pluginId'] = $parameters['pluginId'];
            $sql .= 'AND p.pluginId = :pluginId ';
        }

        // Fetch products
        $result = $productsRepository->fetchByQuery($sql, $payload);

        return $result;
    }
}
