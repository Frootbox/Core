<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\StaticPages\Paypal;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     *
     */
    public function landingpage(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
        \Frootbox\View\Viewhelper\Delegator $delegator
    )
    {


        // $shopcart = new \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart();

        $_SESSION['cart']['paymentmethod']['data']['transaction']['token'] = $get->get('token');
        $_SESSION['cart']['paymentmethod']['data']['transaction']['payerId'] = $get->get('PayerID');

        // Get return address
        if (!empty($get->get('return'))) {
            $url = $get->get('return');
        }
        elseif (!empty($get->get('returnPluginId'))) {
            $plugin = $contentElementsRepository->fetchById($get->get('returnPluginId'));

            $delegator->setObject($plugin);
            $url = $delegator->getActionUri([ 'action' => 'review', 'options' => [ 'absolute' => true] ]);
        }
        else {

            // Obtain checkout plugin
            $plugin = $contentElementsRepository->fetchOne([
                'where' => [
                    'className' => 'Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin'
                ]
            ]);

            $delegator->setObject($plugin);
            $url = $delegator->getActionUri([ 'action' => 'review', 'options' => [ 'absolute' => true] ]);
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}
