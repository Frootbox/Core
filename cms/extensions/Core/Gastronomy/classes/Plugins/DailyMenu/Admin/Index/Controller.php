<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\Index;

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
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuItems $menuItemsRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'menuTemplateId' ]);

        // Fetch template
        $menuTemplate = $menuTemplatesRepository->fetchById($post->get('menuTemplateId'));

        // Create new menu item
        $menuItem = $menuItemsRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\MenuItem([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'parentId' => $menuTemplate->getId(),
            'dateStart' => $get->get('date'),
            'title' => $menuTemplate->getTitle()
        ]));
        $menuItem->addConfig([
            'addition' => $menuTemplate->getConfig('addition'),
            'price' => $menuTemplate->getConfig('price')
        ]);
        $menuItem->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#daysReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\Index\Partials\ListDays::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuItems $menuItemsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch menu item
        $menuItem = $menuItemsRepository->fetchById($get->get('menuItemId'));
        $menuItem->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#daysReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\Index\Partials\ListDays::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch menu templates
        $menuTemplates = $menuTemplatesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);

        $view->set('menuTemplates', $menuTemplates);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuItems $menuItemsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch menu item
        $menuItem = $menuItemsRepository->fetchById($get->get('menuItemId'));

        $view->set('menuItem', $menuItem);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): \Frootbox\Admin\Controller\Response
    {
        d($post);
    }

    /**
     *
     */
    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
