<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Bookings;

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

    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
    ): Response
    {
        $booking = new \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking([

        ]);

        p($booking);
        p($post);
        exit;
    }

    public function ajaxModalComposeAction(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        d($eventRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ]
        ]));
        return new \Frootbox\Admin\Controller\Response('plain', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
    ): Response
    {

        $sql = 'SELECT 
            a.*
        FROM
            assets a,
            assets e
        WHERE
            e.pluginId = ' . $this->plugin->getId() . ' AND 
            a.parentId = e.id AND
            a.className = :bookingName AND
            e.className = :eventClass
        ORDER BY
            a.date DESC';

        // Fetch booking plugins
        $result = $bookingRepository->fetchByQuery($sql, [
            'bookingName' => \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking::class,
            'eventClass' => \Frootbox\Ext\Core\Events\Persistence\Event::class,
        ]);

        return self::getResponse(body: [
            'Bookings' => $result,
        ]);
    }
}
