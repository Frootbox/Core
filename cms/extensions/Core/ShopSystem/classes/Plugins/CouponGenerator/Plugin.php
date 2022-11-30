<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\CouponGenerator;

use Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxAjaxSubmitCouponAction(
        Shopcart $shopcart,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementRepository,
    ): Response
    {
        // Fetch coupon product
        $product = $productRepository->fetchOne([
            'where' => [
                'title' => 'Gutschein',
            ],
        ]);

        if (!$product) {
            throw new \Exception('Coupon product missing.');
        }

        // Add coupon to cart
        $shopcart->addItem($product, [
            'unique' => true,
            'forcePriceGross' => $post->get('value'),
            'type' => 'GenericCoupon',
        ]);

        // Fetch checkout plugin
        $checkoutPlugin = $contentElementRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class
            ]
        ]);

        return new \Frootbox\View\ResponseJson([
            'success' => 'Das Produkt wurde in den Warenkorb gelegt.',
            'continue' => ($checkoutPlugin ? $checkoutPlugin->getActionUri('index', null, [ 'absolute' => true ]) : null),
            'shopcart' => [
                'items' => $shopcart->getItemCount(),
            ],
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }
}
