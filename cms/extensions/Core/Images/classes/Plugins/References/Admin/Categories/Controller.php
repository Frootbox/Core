<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Categories;

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
     * Show categories contacts
     */
    public function ajaxPanelReferencesAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch all available contacts
        $result = $referencesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ]
        ]);

        return self::getResponse('plain', 200, [
            'category' => $category,
            'references' => $result,
        ]);
    }

    /**
     *
     */
    public function ajaxReferenceAddAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch reference
        $reference = $referencesRepository->fetchById($post->get('contactId'));

        // Connect item to category
        $category->connectItem($reference);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#referencesCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Categories\Partials\ReferencesList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxItemDisconnectAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $itemsRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch item
        $item = $itemsRepository->fetchById($get->get('itemId'));

        // Disconnect item from category
        $category->disconnectItem($item);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#referencesCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Categories\Partials\ReferencesList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin,
                ])
            ]
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
