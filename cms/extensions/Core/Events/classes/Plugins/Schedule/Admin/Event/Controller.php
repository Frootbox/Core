<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Event;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venueRepository
     * @return \Frootbox\Db\Result
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

        // Delete booking
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
    public function ajaxBookingMoveAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch booking
        $booking = $bookingRepository->fetchById($get->get('bookingId'));

        // Obtain original event
        $originalEvent = $booking->getEvent();

        // Fetch new event
        $targetEvent = $eventRepository->fetchById($post->get('eventId'));

        // Update booking
        $booking->setParentId($targetEvent->getId());
        $booking->save();

        // Update booked seats
        $targetEvent->refreshBookedSeats();
        $originalEvent->refreshBookedSeats();

        return self::getResponse('json', 200, [
            'success' => 'Die Buchung wurde verschoben.'
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
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\CloningMachine $cloningMachine
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return \Frootbox\Admin\Controller\Response
     * @throws \DateMalformedStringException
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxCloneMultiAction(
        \Frootbox\Http\Post $post,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): \Frootbox\Admin\Controller\Response
    {
        // Get target date
        $targetDay = new \DateTime($post->get('cloneToDay'));

        foreach ($post->get('events') as $eventId) {

            // Fetch source event
            $cloneFrom = $eventRepository->fetchById($eventId);

            $dateStart = new \DateTime($cloneFrom->getDateStart());
            $dateEnd = new \DateTime($cloneFrom->getDateEnd());


            // Build up new event
            $event = new \Frootbox\Ext\Core\Events\Persistence\Event([
                'title' => $cloneFrom->getTitle(),
                'pluginId' => $this->plugin->getId(),
                'pageId' => $this->plugin->getPage()->getId(),
                'parentId' => $cloneFrom->getParentId(),
                'visibility' => (DEVMODE ? 2 : 1),
                'dateStart' => $targetDay->format('Y-m-d') . ' ' . $dateStart->format('H:i:s'),
                'dateEnd' => $targetDay->format('Y-m-d') . ' ' . $dateEnd->format('H:i:s'),
            ]);

            // Persist event
            $event = $eventRepository->insert($event);

            $event->addConfig([
                'bookable' => [
                    'seats' => $cloneFrom->getConfig('bookable.seats'),
                    'price' => $cloneFrom->getConfig('bookable.price'),
                ]
            ]);

            $tags = [];
            foreach ($cloneFrom->getTags() as $tag) {
                $tags[] = $tag->getTag();
            }

            $event->setTags($tags);

            $event->save();

            // Clone content
            $cloningMachine->cloneContentsForElement($event, $cloneFrom->getUidBase());

            foreach ($cloneFrom->getCategories() as $category) {
                $category->connectItem($event);
            }
        }

        $payload = [
            'replace' => [
                'selector' => '#eventsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Archive\Partials\ListEvents\Partial::class, [
                    'plugin' => $this->plugin,
                ]),
            ],
        ];

        if (empty($post->get('KeepOpen'))) {
            $payload['modalDismiss'] = true;
        }

        return self::getResponse('json', 200, $payload);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @param \Frootbox\CloningMachine $cloningMachine
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository,
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

        // Persist event
        $event = $events->insert($event);

        // Clone data if needed
        if (!empty($get->get('cloneFrom'))) {

            $cloneFrom = $events->fetchById($get->get('cloneFrom'));

            $event->addConfig([
                'bookable' => [
                    'seats' => $cloneFrom->getConfig('bookable.seats'),
                    'price' => $cloneFrom->getConfig('bookable.price'),
                ]
            ]);

            $tags = [];
            foreach ($cloneFrom->getTags() as $tag) {
                $tags[] = $tag->getTag();
            }

            $event->setTags($tags);

            $event->save();

            // Clone content
            $cloningMachine->cloneContentsForElement($event, $cloneFrom->getUidBase());

            foreach ($cloneFrom->getCategories() as $category) {
                $category->connectItem($event);
            }
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

        return self::getResponse('json', 200, [
            'success' => 'Die Veranstaltung wurde gelöscht.',
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
     *
     */
    public function ajaxDuplicateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $eventRepository->fetchById($get->get('eventId'));

        $dateStart = new \DateTime($event->getDateStart());
        $dateEnd = new \DateTime($event->getDateEnd());

        for ($i = 1; $i <= $post->get('amount'); ++$i) {

            $dateStart->modify('+' . $post->get('distance') . ' ' . $post->get('interval'));
            $dateEnd->modify('+' . $post->get('distance') . ' ' . $post->get('interval'));

            $newEvent = $event->duplicate();
            $newEvent->setDateStart($dateStart->format('Y-m-d H:i:s'));
            $newEvent->setDateEnd($dateEnd->format('Y-m-d H:i:s'));
            $newEvent->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Veranstaltung wurde ' . $post->get('amount') . ' mal dupliziert.',
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
     * @param \Frootbox\Http\Get $get
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxModalCloneAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        $events = [];

        foreach ($get->get('events') as $eventId => $state) {

            $events[] = $eventRepository->fetchById($eventId);
        }

        return new \Frootbox\Admin\Controller\Response('plain', 200, [
            'events' => $events,
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxModalComposeAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event to be cloned
        if (!empty($get->get('cloneFrom'))) {
            $clone = $events->fetchById($get->get('cloneFrom'));
        }

        return new \Frootbox\Admin\Controller\Response('plain', 200, [
            'clone' => $clone ?? null,
        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxMultiActionAction(
        \Frootbox\Http\Post $post,
    ): \Frootbox\Admin\Controller\Response
    {
        return new \Frootbox\Admin\Controller\Response('json', 200, [
            'success' => null,
            'modalTitle' => 'Veranstaltungen klonen',
            'triggerModal' => $this->plugin->getAdminUri('Event', 'ajaxModalClone', [ 'events' => $post->get('event') ]),
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
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookingRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch booking
        $booking = $bookingRepository->fetchById($get->get('bookingId'));

        // Fetch events
        $events = $eventRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('dateStart', date('Y-m-d H:i:s')),
            ],
            'order' => [ 'dateStart ASC' ],
        ]);


        return self::getResponse(body: [
            'booking' => $booking,
            'events' => $events,
        ]);
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

    /**
     *
     */
    public function duplicateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch event
        $event = $eventRepository->fetchById($get->get('eventId'));

        return self::getResponse(body: [
            'event' => $event,
        ]);
    }
}
