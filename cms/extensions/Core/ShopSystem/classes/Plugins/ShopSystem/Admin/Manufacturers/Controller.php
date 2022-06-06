<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Manufacturers;

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
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Insert new manufacturer
        $manufacturer = $manufacturersRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Manufacturer([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title')
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#manufacturersReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Manufacturers\Partials\ManufacturersList\Partial::class, [
                    'highlight' => $manufacturer->getId(),
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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch manufacturer
        $manufacturer = $manufacturersRepository->fetchById($get->get('manufacturerId'));
        $manufacturer->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#manufacturersReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Manufacturers\Partials\ManufacturersList\Partial::class, [
                    'highlight' => $manufacturer->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch manufacturer
        $manufacturer = $manufacturersRepository->fetchById($get->get('manufacturerId'));

        $manufacturer->setTitle($post->get('title'));
        $manufacturer->save();

        return self::getResponse('json');
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
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Fetch datasheet
        $manufacturer = $manufacturersRepository->fetchById($get->get('manufacturerId'));

        return self::getResponse('html', 200, [
            'manufacturer' => $manufacturer
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
