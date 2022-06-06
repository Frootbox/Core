<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Bookings;

use Frootbox\Admin\Controller\Response;
use Frootbox\Session;

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
    public function ajaxCreateTestAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): Response
    {
        // Fetch random product
        $product = $productsRepository->fetchOne([
            'order' => [ 'rand()' ]
        ]);

        $shopcart->addItem($product);

        // Fetch checkout plugin
        $checkoutPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class
            ]
        ]);

        $url = 'https://randomuser.me/api/';
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        $user = $result['results'][0];


        $personal = [
            'gender' => ucfirst($user['gender']),
            'firstname' => $user['name']['first'],
            'lastname' => $user['name']['last'],
            'street' => $user['location']['street']['name'],
            'streetNumber' => $user['location']['street']['number'],
            'city' => $user['location']['city'],
            'postalCode' => $user['location']['postcode'],
            'email' => $session->getUser()->getEmail(),
            'phone' => $user['phone'],
        ];

        $shopcart->setPersonal($personal);

        return self::getResponse('json', 200, [
            'redirect' => ($checkoutPlugin ? $checkoutPlugin->getUri(null, null, [ 'absolute' => true ]) : null)
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        $method = $booking->getPaymentMethod();

        $summary = $method->renderSummary($view, $booking->getConfig('payment.data') ?? []);

        return self::getResponse('html', 200, [
            'paymentMethod' => $method,
            'summary' => $summary,
            'booking' => $booking
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
