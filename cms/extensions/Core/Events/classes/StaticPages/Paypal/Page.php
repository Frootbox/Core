<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\StaticPages\Paypal;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     *
     */
    public function landingpage(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
        \Frootbox\View\Viewhelper\Delegator $delegator
    ): void
    {
        // Store payment data from paypal
        $_SESSION['events']['booking']['payment']['transaction']['token'] = $get->get('token');
        $_SESSION['events']['booking']['payment']['transaction']['payerId'] = $get->get('PayerID');

        // Fetch event
        $event = $eventRepository->fetchById($_SESSION['events']['booking']['event']['id']);

        // Fetch events plugin
        $plugin = $contentElementsRepository->fetchById($event->getPluginId());

        if (!empty($plugin->getConfig('bookingPluginId'))) {

            // Obtain checkout plugin
            $bookingPlugin = $contentElementsRepository->fetchById($plugin->getConfig('bookingPluginId'));
        }
        else {

            // Obtain checkout plugin
            $bookingPlugin = $contentElementsRepository->fetchOne([
                'where' => [
                    'className' => 'Frootbox\Ext\Core\Events\Plugins\Booking\Plugin'
                ]
            ]);
        }

        $delegator->setObject($bookingPlugin);
        $url = $delegator->getActionUri([ 'action' => 'review', 'options' => [ 'absolute' => true] ]);

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
