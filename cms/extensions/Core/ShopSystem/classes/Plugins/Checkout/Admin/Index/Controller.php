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
     *
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
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
