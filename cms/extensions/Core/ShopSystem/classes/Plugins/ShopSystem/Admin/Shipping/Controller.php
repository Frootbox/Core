<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Post;

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
    public function ajaxCopyToAllAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));

        foreach ($productsRepository->fetch() as $product) {

            $product->setShippingId($shippingCosts->getId());
            $product->save();
        }

        return new Response('json', 200, [
            'success' => 'Die Versandkostenart wurde auf alle Produkte übertragen.',
        ]);
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        // Insert new shippingcosts
        $shippingCosts = $shippingCostsRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts([
            'customClass' => $post->get('shippingcosts')
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#shippingCostsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\ShippingCostsList\Partial::class, [
                    'highlight' => $shippingCosts->getId(),
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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        // Fetch shippingcosts
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));

        $shippingCosts->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#shippingCostsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\ShippingCostsList\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction (
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Fetch available shippingcosts
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $shippingcosts = [];

        foreach ($extensions as $extension) {

            $controller = $extension->getExtensionController();

            $path = $controller->getPath() . 'classes/ShippingCosts/';

            if (!file_exists($path)) {
                continue;
            }

            $dir = new \Frootbox\Filesystem\Directory($path);

            foreach ($dir as $file) {

                $shippingcosts[] = [
                    'class' => $controller->getBaseNamespace() . 'ShippingCosts\\' . $file . '\\ShippingCosts',
                    'title' => $file
                ];
            }
        }

        return self::getResponse('plain', 200, [
            'shippingcosts' => $shippingcosts
        ]);
    }

    /**
     *
     */
    public function ajaxModalEditAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): Response
    {
        // Fetch shopping costs
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));




        return self::getResponse('plain', 200, [

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
