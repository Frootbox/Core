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
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textRepository,
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

        $products = $productsRepository->fetch([
            'order' => [ 'title ASC' ],
        ]);

        foreach ($products as $product) {

            // Fetch description text
            $text = $textRepository->fetchOne([
                'where' => [
                    'uid' => $product->getUid('teaser'),
                ],
            ]);

            $images = $filesRepository->fetch([
                'where' => [
                    'uid' => $product->getUid('image'),
                ],
            ]);

            $imageList = [];

            foreach ($images as $image) {

                $url = $image->getUriDownload([
                    'absolute' => true,
                ]);

                $imageList[] = $url;
            }

            $productData = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice(),
                'taxRate' => $product->getTaxrate(),
                'isVisible' => $product->getVisibility() == 2 ? 1 : 0,
                'text' => $text ? $text->getText() : null,
                'images' => $imageList,
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

        header('Content-Type: application/json; charset=utf-8');
        die(json_encode($export, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
