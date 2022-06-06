<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Event;

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
    public function getAddresses(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venueRepository,
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $venueRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxBookingCancelAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch booking
        $booking = $bookings->fetchById($get->get('bookingId'));
        $booking->delete();


        // Todo: Implement a cancellation log


        // Fetch event
        $event = $events->fetchById($booking->getParentId());

        $event->addConfig([
            'bookable' => [
                'bookedSeats' => $event->getConfig('bookable.bookedSeats') - $booking->getConfig('persons')
            ]
        ]);

        $event->save();


        return self::getResponse('json', 200, [
            'success' => 'Die Buchung wurde storniert.'
        ]);
    }

    /**
     *
     */
    public function ajaxCategorySetAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        if ($get->get('state')) {
            $category->connectItem($event);
        }
        else {
            $category->disconnectItem($event);
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\CloningMachine $cloningMachine
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Build up event
        $event = new \Frootbox\Ext\Core\Events\Persistence\Event([
            'title' => $post->get('title'),
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPage()->getId(),
            'parentId' => (!empty($post->get('venueId')) ? $post->get('venueId') : null),
            'visibility' => (DEVMODE ? 2 : 1),
        ]);

        // Set dates
        if (!empty($post->get('dates'))) {

            if (preg_match('#^(.*?) - (.*?)$#', $post->get('dates'), $match)) {

                $event->setDateStart((new \Frootbox\Dates\Date($match[1]))->format('%Y-%m-%d %H:%M'));
                $event->setDateEnd((new \Frootbox\Dates\Date($match[2]))->format('%Y-%m-%d %H:%M'));
            }
        }

        // Create event
        $event = $events->insert($event);

        // Clone data if needed
        if (!empty($get->get('cloneFrom'))) {

            $cloneFrom = $events->fetchById($get->get('cloneFrom'));

            $event->addConfig([
                'bookable' => [
                    'seats' => $cloneFrom->getConfig('bookable.seats')
                ]
            ]);
            $event->save();

            $cloningMachine->cloneContentsForElement($event, $cloneFrom->getUidBase());
        }

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#eventsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Archive\Partials\ListEvents\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $event->getId()
                ])
            ]
        ]);
    }


    /**
     *
     */
    public function ajaxDeleteAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        $event->delete();

        return self::response('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#eventsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Archive\Partials\ListEvents\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     * Update event
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate requried input
        $post->require([ 'dates' ]);

        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        if (!preg_match('#^(.*?) - (.*?)$#', $post->get('dates'), $match)) {
            throw new \Frootbox\Exceptions\InputInvalid();
        }

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        // Update event
        $event->setTitle($title);
        $event->setParentId($post->getWithDefault('venueId', null));
        $event->setDateStart((new \Frootbox\Dates\Date($match[1]))->format('%Y-%m-%d %H:%M'));
        $event->setDateEnd((new \Frootbox\Dates\Date($match[2]))->format('%Y-%m-%d %H:%M'));

        $event->unsetConfig('titles');
        $event->unsetConfig('noIndividualDetailPage');
        $event->addConfig([
            'titles' => $post->get('titles'),
            'link' => $post->get('link'),
            'noIndividualDetailPage' => $post->get('noIndividualDetailPage'),
            'onlineOnly' => $post->get('onlineOnly'),
            'onlineStream' => $post->get('onlineStream'),
        ]);

        $event->save();

        // Set tags
        $event->setTags($post->get('tags'));

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateBookableAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        // Update event
        $event->addConfig([
            'bookable' => $post->get('bookable')
        ]);
        $event->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxModalComposeAction (
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        // \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
    )
    {
        /*
        // Fetch available venues
        $result = $venues->fetch([
            'where' => [ 'pluginId' => $this->plugin->getId() ]
        ]);

        $view->set('venues', $result);
        */


        // Fetch event to be cloned
        if (!empty($get->get('cloneFrom'))) {
            $clone = $events->fetchById($get->get('cloneFrom'));
        }

        return new \Frootbox\Admin\Controller\Response('plain', 200, [
            'clone' => $clone ?? null,
        ]);
    }

    /**
     *
     */
    public function ajaxSwitchVisibilityAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        $event->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-event="' . $event->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2'
            ],
            'addClass' => [
                'selector' => '.visibility[data-event="' . $event->getId() . '"]',
                'className' => $event->getVisibilityString()
            ]
        ]);
    }

    /**
     *
     */
    public function bookingAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings
    ) {
        // Fetch booking
        $booking = $bookings->fetchById($get->get('bookingId'));
        $view->set('booking', $booking);


        return self::response();
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $events->fetchById($get->get('eventId'));

        /*
        // Fetch venues
        $result = $venues->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId()
            ]
        ]);
        $view->set('venues', $result);*/

        // Fetch events bookings
        $bookings = $bookingRepository->fetch([
            'where' => [
                'parentId' => $event->getId()
            ],
            'order' => [ 'date DESC' ]
        ]);

        // Fetch categories
        $category = $categoriesRepository->fetchOne([
            'where' => [
                'uid' => $this->plugin->getUid('categories'),
                'parentId' => 0
            ]
        ]);

        if ($category) {
            $view->set('categories', $categoriesRepository->getTree($category->getRootId()));
        }

        return self::getResponse(body: [
            'event' => $event,
            'bookings' => $bookings,
        ]);
    }
}
