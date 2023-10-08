<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
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
    public function ajaxModalConfigAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch catebories
        $result = $categoriesRepository->fetch([

        ]);

        return self::getResponse('plain', 200, [
            'categories' => $result,
            'category' => $category,
        ]);
    }

    /**
     *
     */
    public function ajaxModalFilterCriteriaComposeAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
    ): Response
    {
        // Fetch datahsheets
        $datasheets = $datasheets->fetch();

        return self::getResponse('plain', 200, [
            'datasheets' => $datasheets
        ]);
    }

    /**
     *
     */
    public function ajaxFiltersCopyConfigAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        $filters = $category->getConfig('filters');

        foreach ($category->getOffspring() as $child) {

            $child->addConfig([
                'filters' => $filters
            ]);

            $child->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxFilterCriteriaCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        $filters = $category->getConfig('filters');
        $filters[$post->get('fieldId')] = $post->get('fieldId');

        $category->addConfig([
            'filters' => $filters
        ]);

        $category->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#filtersReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories\Partials\FiltersList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxFilterCriteriaDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        $filters = $category->getConfig('filters');

        unset($filters[$get->get('fieldId')]);

        $category->unsetConfig('filters');
        $category->addConfig([
            'filters' => $filters
        ]);

        $category->save();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#filtersReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories\Partials\FiltersList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxLayoutSetAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        $category->addConfig([
            'layoutId' => $post->get('layoutId')
        ]);

        $category->save();

        return self::getResponse('json', 200);
    }

    /**
     * Show categories details
     */
    public function ajaxPanelDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));
        $view->set('category', $category);

        // Fetch products
        $products = $productsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);
        $view->set('products', $products);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxProductAddAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([
            'productId'
        ]);

        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch product
        $product = $productsRepository->fetchById($post->get('productId'));

        // Connect contact to category
        $category->connectItem($product);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#productsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories\Partials\ProductsList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxProductDisconnectAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        // Disconnect product from category
        $category->disconnectItem($product);

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#productsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Categories\Partials\ProductsList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\CategoriesConnections $categoriesConnectionsRepository,
    ): Response
    {
        $orderId = count($get->get('products')) + 1;

        foreach ($get->get('products') as $productId) {

            $connection = $categoriesConnectionsRepository->fetchOne([
                'where' => [
                    'categoryId' => $get->get('categoryId'),
                    'itemId' => $productId,
                ],
            ]);

            $connection->setOrderId($orderId--);
            $connection->save([
                'skipAlias' => true,
            ]);
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxUpdateConfigAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        $category->addConfig([
            'url' => $post->get('url'),
            'targetCategoryId' => $post->get('targetCategoryId'),
        ]);

        $category->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function filterAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        return self::getResponse('html', 200, [
            'category' => $category
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function layoutAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        return self::getResponse('html', 200, [
            'category' => $category
        ]);
    }
}
