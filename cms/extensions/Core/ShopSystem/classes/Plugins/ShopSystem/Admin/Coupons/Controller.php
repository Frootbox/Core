<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Coupons;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
    ): Response
    {
        // Fetch coupon
        $coupon = $couponsRepository->fetchById($get->get('couponId'));

        return self::getResponse('plain', 200, [
            'coupon' => $coupon
        ]);
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([ 'code' ]);

        // Insert new coupon
        $coupon = $couponsRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Coupon([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title'),
            'uid' => $post->get('code'),
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#couponsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Coupons\Partials\CouponsList\Partial::class, [
                    'highlight' => $coupon->getId(),
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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
    ): Response
    {
        // Fetch coupon
        $coupon = $couponsRepository->fetchById($get->get('couponId'));

        $coupon->setTitle($post->get('title'));
        $coupon->setUid($post->get('code'));

        $coupon->addConfig([
            'amount' => $post->get('amount'),
            'value' => $post->get('value'),
            'valuePercent' => $post->get('valuePercent'),
        ]);

        $coupon->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch coupon
        $coupon = $couponsRepository->fetchById($get->get('couponId'));

        // Delete coupon
        $coupon->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'success' => 'Der Coupon wurde gelöscht.',
            'replace' => [
                'selector' => '#couponsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Coupons\Partials\CouponsList\Partial::class, [
                    'plugin' => $this->plugin,
                ]),
            ],
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
