<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\StaticPages\Payment;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function confirm(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
        \DI\Container $container,
        \Frootbox\Db\Db $dbms,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Builder $builder,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    )
    {
        d($shopcart);
    }

    /**
     *
     */
    public function postAuthed(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopCart,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
        \Frootbox\View\Viewhelper\Delegator $delegator,
    )
    {
        d("PJJJLKLJK");
        // Get payment method
        $paymentMethod = $shopCart->getPaymentMethod();

    //    $container->call([ $paymentMethod, 'onPostAuthed' ]);

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

        d($url);

        header('Location: ' . $url);
        exit;
    }


}
