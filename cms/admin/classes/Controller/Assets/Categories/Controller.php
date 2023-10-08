<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-08-06
 */

namespace Frootbox\Admin\Controller\Assets\Categories;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Persistence\Repositories\Categories $categories
     * @param \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxCreate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Categories $categories,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Check required fields
        $post->require([ 'title' ]);

        // Fetch parent category
        $parent = $categories->fetchById($get->get('parentId'));

        // Compose new category
        $className = $parent->getClassName();
        $newCategory = new $className([
            'pageId' => $parent->getPageId(),
            'title' => $post->get('title'),
            'uid' => $parent->getDataRaw('uid'),
            'visibility' => (DEVMODE ? 2 : 1),
        ]);

        if (!empty($parent->getPluginId())) {

            // Fetch plugin
            $plugin = $contentElementsRepository->fetchById($parent->getPluginId());
            $newCategory->setPluginId($parent->getPluginId());
        }
        else {
            $plugin = null;
        }

        $parent->appendChild($newCategory);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#categoriesTreeReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Assets\Categories\Partials\CategoriesManager\Partial::class, [
                    'uid' => $parent->getDataRaw('uid'),
                    'className' => \Frootbox\Ext\Core\HelpAndSupport\Persistence\Category::class,
                    'skipFrame' => true,
                    'plugin' => $plugin,
                ]),
            ],
        ]);
    }

    /**
     * 
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Categories $categories,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category        
        $category = $categories->fetchById($get->get('categoryId'));

        $category->delete();
                
        $plugin = $get->get('pluginId') ? $contentElements->fetchById($get->get('pluginId')) : null;        
        
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#categoriesTreeReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Assets\Categories\Partials\CategoriesManager\Partial::class, [
                    'plugin' => $plugin,
                    'uid' => $category->getDataRaw('uid'),
                    'className' => $category->getClassName(),
                    'skipFrame' => true
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxItemsSort(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\CategoriesConnections $connectionsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $connectionId) {

            // Fetch connection
            $connection = $connectionsRepository->fetchById($connectionId);

            $connection->setOrderId($orderId--);
            $connection->save();
        }

        return self::getResponse('json', 200, []);
    }

    /**
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        $category->setTitle($title);
        $category->setVisibility($post->get('visibility'));

        if (!empty($post->get('titles'))) {
            $category->unsetConfig('titles');
            $category->addConfig([
                'titles' => array_filter($post->get('titles')),
            ]);
        }

        $category->unsetConfig('noGenericDetailsPage');

        if (!empty($post->get('noGenericDetailsPage'))) {
            $category->addConfig([
                'noGenericDetailsPage' => true,
            ]);
        }

        // Update tags
        if (!empty($post->get('tags'))) {
            $category->setTags($post->get('tags'));
        }

        $category->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#categoriesTreeReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Assets\Categories\Partials\CategoriesManager\Partial::class, [
                    'uid' => $category->getDataRaw('uid'),
                    'className' => \Frootbox\Ext\Core\HelpAndSupport\Persistence\Category::class,
                    'skipFrame' => true
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalCompose (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Categories $categories
    ) : Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        return self::getResponse(body: [
            'category' => $category,
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Categories $categories
    ) : Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        return self::getResponse(body: [
            'category' => $category,
        ]);
    }

    /**
     *
     */
    public function ajaxSort(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Categories $categories
    ): Response
    {
        // Extract sitemap
        $data = json_decode($post->get('pageData'), true);

        // Fetch root node
        $rootPage = $categories->fetchById($get->get('parentId'));

        function loop ($pageset, $categories, $parentId, $lft = 0) {

            ++$lft;

            foreach ($pageset as $page) {

                $rgt = loop($page['children'][0], $categories, $page['id'], $lft);

                // Fetch page
                $xpage = $categories->fetchById($page['id']);

                $xpage->setLft($lft);
                $xpage->setRgt($rgt);
                $xpage->setParentId($parentId);

                $xpage->save([
                    'skipAlias' => true
                ]);

                $lft = $rgt + 1;
            }

            return $lft;
        }

        $rgt = loop($data[0], $categories, $rootPage->getId(),1);

        // Update root node
        $rootPage->setLft(1);
        $rootPage->setRgt($rgt);
        $rootPage->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxSwitchVisibility(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Categories $categoriesrepository,
    ): Response
    {
        // Fetch asset
        $category = $categoriesrepository->fetchById($get->get('categoryId'));

        $category->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-asset="' . $category->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2',
            ],
            'addClass' => [
                'selector' => '.visibility[data-asset="' . $category->getId() . '"]',
                'className' => $category->getVisibilityString(),
            ],
        ]);
    }

    /**
     *
     */
    public function index(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function sort(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Categories $categories
    ): Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        $parent = $categories->fetchById($category->getRootId());

        $view->set('parent', $parent);

        return self::getResponse();
    }
}
