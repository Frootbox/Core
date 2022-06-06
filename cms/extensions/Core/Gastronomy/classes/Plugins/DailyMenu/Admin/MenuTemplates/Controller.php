<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\MenuTemplates;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        $menuTemplate = $menuTemplatesRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\MenuTemplate([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'title' => $post->get('title'),
            'config' => [
                'addition' => $post->get('addition'),
                'price' => $post->get('price')
            ]
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#menuTemplatesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\MenuTemplates\Partials\ListMenuTemplates::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $menuTemplate->getId()
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch menu template
        $menuTemplate = $menuTemplatesRepository->fetchById($get->get('menuTemplateId'));

        $view->set('menuTemplate', $menuTemplate);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\View $view,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch menu template
        $menuTemplate = $menuTemplatesRepository->fetchById($get->get('menuTemplateId'));

        $menuTemplate->setTitle($post->get('title'));
        $menuTemplate->addConfig([
            'addition' => $post->get('addition'),
            'price' => $post->get('price')
        ]);

        $menuTemplate->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#menuTemplatesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\MenuTemplates\Partials\ListMenuTemplates::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $menuTemplate->getId()
                ])
            ]
        ]);
    }

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
    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
