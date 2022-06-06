<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products;

use Frootbox\Admin\Controller\Response;
use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;
use Frootbox\Http\Get;
use Frootbox\Http\Post;
use Frootbox\Persistence\Repositories\Extensions;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalComposeAction (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
    ): Response
    {
        // Fetch datasheets
        $result = $datasheets->fetch([

        ]);

        $view->set('datasheets', $result);


        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEquipmentComposeAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch products
        $products = $productsRepository->fetch([
            'order' => [ 'title ASC' ]
        ]);

        return self::getResponse('plain', 200, [
            'products' => $products
        ]);
    }

    /**
     *
     */
    public function ajaxModalEquipmentEditAction(
        Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch products
        $product = $productsRepository->fetchById($get->get('productId'));

        $equipment = $product->getEquipmentById($get->get('equipmentId'));

        return self::getResponse('plain', 200, [
            'equipment' => $equipment,
        ]);
    }

    /**
     *
     */
    public function ajaxModalRecommendationsComposeAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch products
        $products = $productsRepository->fetch([
            'order' => [ 'title ASC' ]
        ]);

        return self::getResponse('plain', 200, [
            'products' => $products
        ]);
    }

    /**
     * @param Get $get
     * @param Products $productsRepository
     * @return Response
     */
    public function ajaxModalStocksComposeAction(
        Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        return new Response('plain', 200, [
            'product' => $product,
        ]);
    }

    /**
     *
     */
    public function ajaxModalStocksEditAction(
        Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
    ): Response
    {
        // Fetch stocks
        $stock = $stockRepository->fetchById($get->get('stockId'));

        return new Response('plain', 200, [
            'stock' => $stock,
        ]);
    }

    /**
     *
     */
    public function ajaxModalVariantComposeAction(
        Get $get
    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalVariantEditAction(
        Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository
    ): Response
    {
        // Fetch variant
        $variant = $variantsRepository->fetchById($get->get('variantId'));
        $view->set('variant', $variant);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction (
        Post $post,
        Get $get,
        \Frootbox\Db\Db $database,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetsRepository,
        Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([
            'title'
        ]);

        // Start database transaction
        $database->transactionStart();

        // Fetch datasheet
        $datasheet = $datasheetsRepository->fetchById($post->get('datasheetId'));

        // Insert new product
        $product = $productsRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Product([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'datasheetId' => $datasheet->getId(),
            'title' => $post->get('title')
        ]));

        if (!empty($get->get('categoryId'))) {

            $category = $categriesRepository->fetchById($get->get('categoryId'));

            // Connect contact to category
            $category->connectItem($product);

            $database->transactionCommit();

            // Compose response
            return self::getResponse('json', 200, [
                'modalDismiss' => true,
                'replace' => [
                    'selector' => '#productsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories\Partials\ProductsList\Partial::class, [
                        'category' => $category,
                        'plugin' => $this->plugin,
                        'highlight' => $product->getId(),
                    ])
                ]
            ]);
        }
        else {

            $database->transactionCommit();

            // Compose response
            return self::getResponse('json', 200, [
                'modalDismiss' => true,
                'replace' => [
                    'selector' => '#productsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\ProductsList\Partial::class, [
                        'highlight' => $product->getId(),
                        'plugin' => $this->plugin
                    ])
                ]
            ]);
        }
    }

    /**
     *
     */
    public function ajaxDeleteAction (
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        $product->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#productsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\ProductsList\Partial::class, [
                    'highlight' => $product->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxEquipmentAddAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch equipment
        $equipment = $productsRepository->fetchById($post->get('productId'));

        // Add equipment
        $product->equipmentAdd($equipment);

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#equipmentReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\EquipmentList\Partial::class, [
                    'highlight' => $equipment->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxEquipmentDeleteAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch equipment
        $equipment = $productsRepository->fetchById($get->get('equipmentId'));

        // Add equipment
        $product->equipmentRemove($equipment);

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#equipmentReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\EquipmentList\Partial::class, [
                    'highlight' => $equipment->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxEquipmentUpdateAction(
        Get $get,
        Post $post,
        Products $productsRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch equipment
        $equipment = $product->getEquipmentById($get->get('equipmentId'));

        $equipment['amount'] = $post->get('amount');
        $equipment['autoAddToCart'] = $post->get('autoAddToCart');
        $equipment['noExtraCharge'] = $post->get('noExtraCharge');
        $equipment['forceAmount'] = $post->get('forceAmount');

        $product->equipmentUpdate($equipment);
        $product->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxProductsSortAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#productsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\ProductsList\Partial::class, [
                    'sort' => [
                        'column' => $get->get('column'),
                        'direction' => $get->get('direction'),
                    ],
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxRecommendationAddAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch recommendation
        $recommendation = $productsRepository->fetchById($post->get('productId'));

        // Add equipment
        $product->recommendationAdd($recommendation);

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#recommendationsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\RecommendationsList\Partial::class, [
                    'highlight' => $recommendation->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxRecommendationDeleteAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch equipment
        $recommendation = $productsRepository->fetchById($get->get('recommendationId'));

        // Add equipment
        $product->recommendationRemove($recommendation);


        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#recommendationsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\RecommendationsList\Partial::class, [
                    'highlight' => $recommendation->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxStocksCreateAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        $groupsData = $post->get('groups');
        $groupsData = array_filter($groupsData);

        ksort($groupsData, SORT_NUMERIC);
        $key = md5(json_encode($groupsData));

        $stock = new \Frootbox\Ext\Core\ShopSystem\Persistence\Stock([
            'productId' => $product->getId(),
            'amount' => strlen($post->get('amount')) > 0 ? $post->get('amount') : null,
            'groupKey' => $key,
            'groupData' => json_encode($groupsData),
        ]);

        $stockRepository->insert($stock);

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#stocksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\StocksList\Partial::class, [
                    'highlight' => $stock->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxStocksDeleteAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
    ): Response
    {
        // Fetch stock
        $stock = $stockRepository->fetchById($get->get('stockId'));

        // Fetch product
        $product = $productsRepository->fetchById($stock->getProductId());

        $stock->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#stocksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\StocksList\Partial::class, [
                    'product' => $product,
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxStocksUpdateAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
    ): Response
    {
        // Fetch stock
        $stock = $stockRepository->fetchById($get->get('stockId'));

        // Fetch product
        $product = $productsRepository->fetchById($stock->getProductId());

        $stock->setAmount($post->get('amount'));
        $stock->setPrice($post->get('price') * 100);
        $stock->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#stocksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\StocksList\Partial::class, [
                    'highlight' => $stock->getId(),
                    'product' => $product,
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxSwitchVisibleAction(
        Get $get,
        Products $productsRepository
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        $product->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-product="' . $product->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2'
            ],
            'addClass' => [
                'selector' => '.visibility[data-product="' . $product->getId() . '"]',
                'className' => $product->getVisibilityString()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateInfoAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData $productsDataRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\CategoriesConnections $connectionsRepository,
    ): Response
    {
        // Validate required input
        $post->requireOne([ 'title', 'titles' ]);

        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Update product
        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        $product->setTitle($title);

        $product->setPageId($this->plugin->getPage()->getId());
        $product->setItemNumber((string) $post->get('itemNumber'));
        $product->setPrice((float) $post->get('price'));
        $product->setTaxrate((float) $post->get('taxrate'));
        $product->setManufacturerId(!empty($post->get('manufacturerId')) ? $post->get('manufacturerId') : null);

        $product->setShippingId(!empty($post->get('shippingcosts')) ? $post->get('shippingcosts') : null);
        $product->setShippingState($post->get('shippingState'));

        $product->setPackagingUnit($post->get('packagingUnit'));
        $product->setPackagingSize($post->get('packagingSize'));
        $product->setMinimumAge($post->get('minimumAge'));

        $product->addConfig([
            'priceOld' => $post->get('priceOld')
        ]);

        if (!empty($post->get('titles'))) {
            $product->unsetConfig('titles');
            $product->addConfig([
                'titles' => array_filter($post->get('titles')),
            ]);
        }

        // Set tags
        $product->setTags($post->get('tags'));

        // Update products datsheet
        if (!empty($newDatasheetId = $post->get('datasheetId')) and $newDatasheetId != $product->getDatasheetId()) {

            // Fetch new datasheet
            $datasheet = $datasheetsRepository->fetchById($newDatasheetId);


            // Cleanup old data fields
            $result = $productsDataRepository->fetch([
                'where' => [
                    'productId' => $product->getId()
                ]
            ]);

            $result->map('delete');

            $product->setDatasheetId($datasheet->getId());
        }

        $product->save();

        // Update connections
        $result = $connectionsRepository->fetch([
            'where' => [
                'itemId' => $product->getId(),
            ],
        ]);

        foreach ($result as $connection) {
            $connection->save();
        }


        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateDatasheetFieldsAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData $productsDataRepository
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        foreach ($post->get('fields') as $fieldId => $value) {

            /* @var \Frootbox\Ext\Core\ShopSystem\Persistence\ProductData $field */
            $field = $productsDataRepository->fetchOne([
                'where' => [
                    'fieldId' => $fieldId,
                    'productId' => $product->getId()
                ]
            ], [
                'createOnMiss' => true
            ]);

            $field->setValueText($value);
            $field->updateMetrics();
            $field->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxVariantCreateAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Insert new variant
        $variant = $variantsRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Variant([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'parentId' => $product->getId(),
            'title' => $post->get('title')
        ]));

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#variantsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\VariantsList::class, [
                    'product' => $product,
                    'plugin' => $this->plugin,
                    'highlight' => $variant->getId()
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxVariantDeleteAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch variant
        $variant = $variantsRepository->fetchById($get->get('variantId'));

        $variant->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#variantsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\VariantsList::class, [
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxVariantUpdateAction(
        Get $get,
        Post $post,
        Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch variant
        $variant = $variantsRepository->fetchById($get->get('variantId'));

        // Fetch product
        $product = $productsRepository->fetchById($variant->getParentId());

        $variant->setTitle($post->get('title'));
        $variant->setPrice($post->get('price'));
        $variant->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#variantsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\VariantsList::class, [
                    'product' => $product,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        Get $get,
        Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Fetch datasheet
        $datasheet = $datasheetsRepository->fetchById($product->getDatasheetId());

        // Fetch available datasheets
        $datasheets = $datasheetsRepository->fetch();

        // Fetch available shippingcosts
        $shippingcosts = $shippingCostsRepository->fetch();

        // Fetch available manufacturers
        $manufacturers = $manufacturersRepository->fetch();

        return self::getResponse('html', 200, [
            'product' => $product,
            'datasheet' => $datasheet,
            'datasheets' => $datasheets,
            'shippingcosts' => $shippingcosts,
            'manufacturers' => $manufacturers
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
