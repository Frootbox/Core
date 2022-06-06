<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showEvent'
    ];

    /**
     * Generate booking url
     */
    public function getBookingPlugin(): ?\Frootbox\Ext\Core\Events\Plugins\Booking\Plugin
    {
        if (empty($this->getConfig('bookingPluginId'))) {
            return null;
        }

        return $this->getModel()->fetchById($this->getConfig('bookingPluginId'));
    }

    /**
     * Fetch top categories
     */
    public function getCategoriesTop(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch top categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'parentId'),
                'visibility' => 1
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getPath ( ) : string {

        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getEvents (
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    )
    {

        $sql = 'SELECT
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber,
            l.config as locationConfig
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND  
            e.pluginId = ' . $this->getId() . ' AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            )            
        ORDER BY
            e.dateStart ASC';


        // Fetch events
        /* @var \Frootbox\Db\Result $result */
        $result = $events->fetchByQuery($sql);

        return $result;
    }

    /**
     * Cleanup before deleting plugin
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Persistence\Repositories\Categories $categoryRepository,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venueRepository,
    ): void
    {
        // Cleanup events
        $result = $events->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $result->map('delete');

        // Cleanup categories
        $categories = $categoryRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
            'order' => [ 'lft DESC' ],
        ]);

        $categories->map('delete');

        // Cleanup venues
        $venues = $venueRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $venues->map('delete');
    }

    /**
     *
     */
    public function bookingAction(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository
    ): Response
    {
        // Fetch event
        $event = $eventsRepository->fetchbyId($this->getAttribute('eventId'));

        return new Response([
            'event' => $event,
        ]);
    }


    /**
     *
     */
    public function showEventAction (
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    ) {
        // Fetch event
        $event = $events->fetchById($this->getAttribute('eventId'));

        $view->set('event', $event);
    }
}
