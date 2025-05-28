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
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository
     * @param \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository
     * @return \Frootbox\Admin\Controller\Response
     */
    public function exportAction(
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venuesRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        $exportData = [
            'events' => [],
            'categories' => [],
            'bookings' => [],
            'venues' => [],
        ];

        // Fetch events
        $result = $eventsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $event) {

            $eventData = $event->getData();

            if (!empty($eventData['config'])) {
                $eventData['config'] = json_decode($eventData['config'], true);
            }

            $eventData['categories'] = [];

            foreach ($event->getCategories() as $category) {
                $eventData['categories'][] = $category->getId();
            }

            // Parse bookings
            $bookings = $event->getBookings();
            $eventData['bookings'] = [];

            if ($bookings->getCount() > 0) {
                foreach ($bookings as $booking) {

                    $bookingData = $booking->getConfig();
                    $bookingData['date'] = $booking->getDate();
                    $eventData['bookings'][] = $bookingData;
                }
            }

            $exportData['events'][] = $eventData;
        }

        // Fetch categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $category) {

            $categoryData = $category->getData();

            if (!empty($text = $textsRepository->fetchByUid($category->getUid('title')))) {

                if (!empty($text->getConfig('headline'))) {
                    $categoryData['title'] = $text->getConfig('headline');
                }

                if (!empty($text->getConfig('subtitle'))) {
                    $categoryData['title2'] = $text->getConfig('subtitle');
                }
            }

            if (!empty($file = $filesRepository->fetchByUid($category->getUid('image')))) {

                $fileData = $file->getData();
                $fileData['url'] = $file->getUriThumbnail();

                $categoryData['image'] = $fileData;
            }

            $exportData['categories'][] = $categoryData;
        }

        // Fetch venues
        $result = $venuesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $venue) {
            $exportData['venues'][] = $venue->getData();
        }

        die(json_encode($exportData));
    }

    public function importAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venuesRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Clear events
        $result = $eventsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $result->map('delete');

        // Clear categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'lft DESC' ],
        ]);

        $result->map('delete');

        // Clear venues
        $result = $venuesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $result->map('delete');

        // Prepare imports
        $data = json_decode($post->get('Json'), true);
        $connections = [
            'categories' => [],
            'venues' => [],
        ];

        // Import categories
        $root = new \Frootbox\Ext\Core\Events\Persistence\Category([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'title' => 'Index',
        ]);

        $root = $categoriesRepository->insertRoot($root);

        foreach ($data['categories'] as $categoryData) {

            if ($categoryData['title'] == 'Index') {
                continue;
            }

            $oldId = $categoryData['id'];

            // Clear data
            unset($categoryData['id'], $categoryData['lft'], $categoryData['rgt'], $categoryData['alias']);

            $categoryData['pluginId'] = $this->plugin->getId();
            $categoryData['pageId'] = $this->plugin->getPageId();
            $categoryData['uid'] = $this->plugin->getUid('categories');

            // Compose new category
            $newCategory = new \Frootbox\Ext\Core\Events\Persistence\Category($categoryData);

            $root->appendChild($newCategory);

            $connections['categories'][$oldId] = $newCategory;
        }

        // Import venues
        foreach ($data['venues'] as $venueData) {

            $oldId = $venueData['id'];

            // Clear data
            unset($venueData['id']);

            // Compose new venue
            $newVenue = new \Frootbox\Ext\Core\Events\Persistence\Venue($venueData);
            $venuesRepository->persist($newVenue);

            $connections['venues'][$oldId] = $newVenue;

        }


        // Import events
        foreach ($data['events'] as $eventData) {

            $oldId = $eventData['id'];
            $categories = $eventData['categories'];

            // Clear data
            unset($eventData['id'], $eventData['alias'], $eventData['categories']);

            // Get venue
            $newVenue = $connections['venues'][$eventData['parentId']];

            $eventData['pluginId'] = $this->plugin->getId();
            $eventData['pageId'] = $this->plugin->getPageId();
            $eventData['parentId'] = $newVenue->getId();

            // Compose new event
            $newEvent = new \Frootbox\Ext\Core\Events\Persistence\Event($eventData);

            $eventsRepository->persist($newEvent);

            foreach ($categories as $oldId) {

                $category = $connections['categories'][$oldId];

                $category->connectItem($newEvent);
            }
        }

        d($data);
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
