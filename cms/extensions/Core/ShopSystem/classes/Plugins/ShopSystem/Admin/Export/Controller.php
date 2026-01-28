<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Export;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }

    public function exportJsonAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        $export = [
            'products' => [],
            'categories' => [],
        ];

        $categories = $categoriesRepository->fetch();

        foreach ($categories as $category) {
            $export['categories'][] = [
                'id' => $category->getId(),
                'title' => $category->getTitle(),
            ];
        }

        $products = $productsRepository->fetch();

        foreach ($products as $product) {


            $productData = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'categories' => [],
            ];


            foreach ($product->getCategories() as $category) {
                $productData['categories'][] = [
                    'id' => $category->getId(),
                    'title' => $category->getTitle(),
                ];
            }

            $export['products'][] = $productData;
        }


        d($export);
    }
}
