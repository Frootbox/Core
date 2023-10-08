<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Product\Admin\Index;

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
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->plugin->addConfig([
            'productId' => $post->get('productId'),
        ]);
        $this->plugin->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
    ): Response
    {
        // Fetch products
        $products = $productRepository->fetch();

        return self::getResponse(body: [
            'products' => $products,
        ]);
    }
}
