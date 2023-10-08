<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives;

use Frootbox\Admin\Controller\Response;
use Frootbox\Admin\View;

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
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): Response
    {
        // Insert new additive
        $additive = $additivesRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Additive([
            // 'orderId' => $post->get('orderId'),
            'title' => $post->get('title'),
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'config' => [
                'symbol' => $post->get('orderId'),
            ],
        ]));

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#additivesListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives\Partials\ListAdditives\Partial::class, [
                    'highlight' => $additive->getId(),
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
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): Response
    {
        // Fetch additive
        $additive = $additivesRepository->fetchById($get->get('additiveId'));

        // Delete additive
        $additive->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#additivesListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives\Partials\ListAdditives\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository,
    ): Response
    {
        // Fetch additive
        $additive = $additivesRepository->fetchById($get->get('additiveId'));

        // Update additive
        $additive->addConfig([
            'symbol' => $post->get('symbol'),
        ]);
        $additive->setTitle($post->get('title'));
        $additive->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#additivesListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives\Partials\ListAdditives\Partial::class, [
                    'highlight' => $additive->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): Response
    {
        // Fetch additive
        $additive = $additivesRepository->fetchById($get->get('additiveId'));
        $view->set('additive', $additive);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
