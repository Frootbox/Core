<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Admin\Index;

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
     * @param \Frootbox\Http\Post $post
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): \Frootbox\Admin\Controller\Response
    {
        // Set new config
        $this->plugin->addConfig([
            'skipLinkage' => $post->get('skipLinkage'),
            'skipCoupons' => $post->get('skipCoupons'),
            'skipShipping' => $post->get('skipShipping'),
            'showNetPrices' => $post->get('showNetPrices'),
            'skipCustomerLogin' => $post->get('skipCustomerLogin'),
            'ownOrderNumber' => $post->get('ownOrderNumber'),
            'shopInactive' => [
                'from' => $post->get('dateFrom'),
                'to' => $post->get('dateTo'),
            ],
            'SelfPickupTimes' => $post->get('SelfPickupTimes'),
            'PaymentExtraStep' => !empty($post->get('PaymentExtraStep')),
            'ShowProgressBar' => $post->getBoolean('ShowProgressBar'),
            'BasePluginId' => $post->get('BasePluginId'),
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     * @return \Frootbox\Admin\Controller\Response
     */
    public function indexAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): \Frootbox\Admin\Controller\Response
    {
        $plugins = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin::class,
            ],
        ]);

        return self::getResponse(
            body: [
                'plugins' => $plugins,
            ],
        );
    }
}
