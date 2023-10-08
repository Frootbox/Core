<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Configuration;

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
    public function getForms(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $formsRepository->fetch([

        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxBookingsRecalculateAction(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch events
        $events = $eventRepository->fetch();

        foreach ($events as $event) {

            $bookedSeats = 0;

            foreach ($event->getBookings() as $booking) {
                $bookedSeats += $booking->getPersons();
            }

            $event->addConfig([
                'bookable' => [
                    'bookedSeats' => $bookedSeats,
                ],
            ]);

            $event->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxPricesSetAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch events
        $events = $eventRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($events as $event) {

            $event->addConfig([
                'bookable' => [
                    'price' => $post->get('price'),
                ],
            ]);
            $event->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxTruncateBookingsAction(
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch events
        $events = $eventRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $count = 0;

        foreach ($events as $event) {

            // Fetch bookings
            $bookings = $bookingRepository->fetch([
                'where' => [
                    'parentId' => $event->getId(),
                ],
            ]);

            $count += $bookings->getTotal();

            $bookings->map('delete');

            // Update events bookings
            $event->addConfig([
                'bookable' => [
                    'bookedSeats' => 0,
                ],
            ]);

            $event->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Es wurden ' . $count . ' Buchungen wurden gelöscht.',
        ]);
    }

    /**
     *
     */
    public function ajaxTruncateEventsAction(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch events
        $events = $eventRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $events->map('delete');

        return self::getResponse('json', 200, [
            'success' => 'Alle Veranstaltungen wurden gelöscht.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noEventsDetailPage' => $post->get('noEventsDetailPage'),
            'bookingPluginId' => $post->get('bookingPluginId'),
            'formId' => $post->get('formId'),
            'aliasSubFolder' => $post->get('aliasSubFolder'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $eventsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noEventsDetailPage' => $post->get('noEventsDetailPage'),
                'aliasSubFolder' => $post->get('aliasSubFolder'),
            ]);

            $entity->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch booking plugins
        $result = $contentElements->fetch([
            'where' => [
                'className' => 'Frootbox\\Ext\\Core\\Events\\Plugins\\Booking\\Plugin',
            ],
        ]);

        return self::getResponse(body: [
            'bookingPlugins' => $result,
        ]);
    }
}
