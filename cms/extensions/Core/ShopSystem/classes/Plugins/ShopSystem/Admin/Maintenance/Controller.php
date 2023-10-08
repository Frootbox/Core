<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Maintenance;

use Frootbox\Admin\Controller\Response;
use Frootbox\Admin\View;
use Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod;
use Frootbox\Ext\Core\ShopSystem\NewsletterConnectors\Connector;

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
    public function ajaxOrdersClearAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch orders
        $bookings = $bookingsRepository->fetch([
            'where' => [

            ],
        ]);

        foreach ($bookings as $booking) {
            $booking->delete();
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Bestellungen wurden gelöscht.',
        ]);
    }
}
