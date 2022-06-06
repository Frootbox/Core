<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-04-16
 */

namespace Frootbox\Admin\Controller\NavigationBars;

use DI\Container;
use Frootbox\Admin\Controller\Response;
use Frootbox\Db\Db;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxCreate(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // Insert new navigation
        $navigation = $navigationsRepository->insert(new \Frootbox\Persistence\Navigation([
            'title' => $post->get('title'),
            'navId' => $post->get('navId'),
        ]));

        return self::getResponse('json', 200, [
            'replacements' => [
                [
                    'selector' => '#navBarsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\NavigationBars\Partials\ListNavigations\Partial::class, [
                        'highlight' => $navigation->getId(),
                    ]),
                ],
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxItemCreate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        // Create navigation item
        $className = $post->get('className');
        $item = new $className([
            'navId' => $navigation->getId(),
            'parentId' => ($get->get('parentId') ?? 0),
            'title' => $post->get('title'),
            'language' => (!empty($get->get('language')) ? $get->get('language') : GLOBAL_LANGUAGE),
            'visibility' => (DEVMODE ? 2 : 1),
        ]);

        $navigationItem = $navigationsItemsRepository->insert($item);

        if (method_exists($navigationItem, 'initialize')) {
            $container->call([ $navigationItem, 'initialize' ]);
        }

        return self::getResponse('json', 200, [
            'replacements' => [
                [
                    'selector' => '#navItemsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\NavigationBars\Partials\ListNavigationItems\Partial::class, [
                        'navigation' => $navigation,
                        'highlight' => $navigationItem->getId(),
                        'language' => $item->getLanguage(),
                    ]),
                ],
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxItemDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        // Fetch item
        $item = $navigationsItemsRepository->fetchById($get->get('itemId'));

        $item->delete();

        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($item->getNavId());

        return self::getResponse('json', 200, [
            'success' => 'Der Menüpunkt wurde gelöscht.',
            'replacements' => [
                [
                    'selector' => '#navItemsReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Admin\Controller\NavigationBars\Partials\ListNavigationItems\Partial::class, [
                        'navigation' => $navigation,
                    ]),
                ],
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxItemSort(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $itemId) {

            // Fetch navigation
            $item = $navigationsItemsRepository->fetchById($itemId);

            $item->setOrderId($orderId--);
            $item->save();
        }

        return self::getResponse('json', 200, [ ]);
    }

    /**
     *
     */
    public function ajaxItemUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        // Fetch item
        $item = $navigationsItemsRepository->fetchById($get->get('itemId'));

        $item->setTitle($post->get('title'));

        $item->updateFromPost($post);

        $item->unsetConfig('icon');
        $item->unsetConfig('iconOnly');

        $item->addConfig([
            'icon' => $post->get('icon'),
            'iconOnly' => $post->get('iconOnly'),
        ]);

        $item->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxSort(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        if (!empty($post->get('pageData'))) {

            $data = json_decode($post->get('pageData'), true);

            function loop ( int $parentId, array $children, $navigationsItemsRepository) {

                $orderId = count($children) + 1;

                foreach ($children as $child) {

                    // Fetch item
                    $item = $navigationsItemsRepository->fetchById($child['id']);

                    $item->setOrderId(--$orderId);
                    $item->setParentId($parentId);
                    $item->save();

                    if (!empty($child['children'][0])) {
                        loop($item->getId(), $child['children'][0], $navigationsItemsRepository);
                    }
                }
            }

            loop(0, $data[0], $navigationsItemsRepository);
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        $navigation->setTitle($post->get('title'));
        $navigation->setNavId($post->get('navId'));

        $navigation->save();

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxModalCompose(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        return self::getResponse('html', 200, [

        ]);
    }


    /**
     *
     */
    public function ajaxModalComposeItem(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Fetch extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $sections = [];

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath() . 'classes/Navigations/Items/';

            if (!file_exists($path)) {
                continue;
            }

            $dir = new \Frootbox\Filesystem\Directory($path);

            foreach($dir as $file) {

                if ($file == 'AbstractItem.php' or $file == 'Dummy.php') {
                    continue;
                }

                $className = 'Frootbox\\Ext\\' . $extension->getVendorId() . '\\' . $extension->getExtensionId() . '\\Navigations\\Items\\' . $file . '\\Item';
                $title = $file;

                $languageFile = $dir->getPath() . $file . '/resources/private/language/de-DE.php';

                if (file_exists($languageFile)) {

                    $data = require $languageFile;

                    if (!empty($data['Item.Title'])) {
                        $title = $data['Item.Title'];
                    }
                }

                $key = $extension->getVendorId() . '/' . $extension->getExtensionId();

                $sections[$key][] = [
                    'title' => $title,
                    'className' => $className,
                ];
            }
        }

        return self::getResponse('html', 200, [
            'sections' => $sections,
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        return self::getResponse('html', 200, [
            'navigation' => $navigation,
        ]);
    }

    /**
     *
     */
    public function ajaxModalItemDetails(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository
    ): Response
    {
        // Fetch item
        $item = $navigationsItemsRepository->fetchById($get->get('itemId'));

        $adminFile = $item->getPath() . 'resources/private/views/Admin.html.twig';

        if (file_exists($adminFile)) {
            $html = $view->render($adminFile, dirname($adminFile) . '/', [
                'item' => $item,
            ]);
        }
        else {
            $html = (string) null;
        }

        return self::getResponse('html', 200, [
            'item' => $item,
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function details(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        return self::getResponse('html', 200, [
            'navigation' => $navigation,
        ]);
    }

    /**
     *
     */
    public function index(

    ): Response
    {


        return self::getResponse();
    }

    /**
     *
     */
    public function sort(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // Fetch navigation
        $navigation = $navigationsRepository->fetchById($get->get('navigationId'));

        return self::getResponse('html', 200, [
            'navigation' => $navigation,
        ]);
    }
}
