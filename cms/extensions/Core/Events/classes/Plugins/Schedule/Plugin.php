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
        'showEvent',
        'showVenue',
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
        array $parameters = null,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Categories $categoryRepository,
    ): \Frootbox\Db\Result
    {
        if (empty($parameters['onlyWithEvents'])) {

            // Fetch top categories
            $result = $categoryRepository->fetch([
                'where' => [
                    'uid' => $this->getUid('categories'),
                    new \Frootbox\Db\Conditions\MatchColumn('rootId', 'parentId'),
                    new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
                ],
            ]);
        }
        else {

            // Build sql
            $sql = 'SELECT
                c.id,                
                ANY_VALUE(c.title) as title,
                MIN(e.dateStart) as dateStart
            FROM
                assets e,
                categories c,
                categories_2_items cx
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
                ) AND
                cx.categoryClass = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Category" AND
                c.id = cx.categoryId AND
                cx.itemId = e.id
            GROUP BY 
                c.id                            
            ORDER BY
                dateStart ASC';


            // Fetch categories
            $result = $categoryRepository->fetchByQuery($sql);
        }

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
    public function getAvailableTags(array $parameters = null): \Frootbox\Db\Result
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Tags::class);

        $payload = [
            'className' => \Frootbox\Ext\Core\Events\Persistence\Event::class,
        ];

        $sql = 'SELECT 
            COUNT(t.id) as count, 
            t.tag as tag
        FROM
            tags t,
            assets a
        WHERE
            SUBSTR(t.tag, 1, 1) != "_" AND
            t.itemClass = :className AND
            a.className = t.itemClass AND
            a.id = t.itemId AND
            a.visibility >= ' . (IS_EDITOR ? 1 : 2) . '       
            ';

        if (!empty($parameters['exclude'])) {

            $sql .= ' AND t.tag NOT IN ( ';
            $comma = '';

            foreach ($parameters['exclude'] as $index => $tag) {
                $sql .= $comma . ':tag_' . $index;
                $comma = ', ';

                $payload['tag_' . $index] = $tag;
            }

            $sql .= ' ) ';
        }

        $sql .= ' GROUP BY
            t.tag
        ORDER BY        
            t.tag ASC';

        $result = $tagsRepository->fetchByQuery($sql, $payload);

        return $result;
    }

    /**
     *
     */
    public function getEvents(
        array $options = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber,
            l.zipcode as locationZipcode,
            l.city as locationCity,
            l.phone as locationPhone,
            l.email as locationEmail,
            l.config as locationConfig,
            l.alias as locationAlias
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND  
            e.pluginId = ' . $this->getId() . ' AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" ';

        if (empty($options['showPastDates'])) {

            $sql .= ' AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            ) ';
        }


        $sql .= ' ORDER BY
            e.dateStart ASC';

        // Fetch events
        $eventRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Events::class);
        $result = $eventRepository->fetchByQuery($sql);

        if (!empty($options['onlyBookable'])) {

            foreach ($result as $index => $event) {

                if (!$event->isBookable()) {
                    $result->removeByIndex($index);
                }
            }
        }

        return $result;
    }

    /**
     *
     */
    public function getEventsPast(
        array $options = null,
    ): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber,
            l.zipcode as locationZipcode,
            l.city as locationCity,
            l.phone as locationPhone,
            l.email as locationEmail,
            l.config as locationConfig,
            l.alias as locationAlias
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND  
            e.pluginId = ' . $this->getId() . ' AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" ';


            $sql .= ' AND e.dateEnd <= "' . date('Y-m-d H:i:s') . '" ';


        $sql .= ' ORDER BY
            e.dateStart DESC';

        // Fetch events
        $eventRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Events::class);
        $result = $eventRepository->fetchByQuery($sql);

        if (!empty($options['onlyBookable'])) {

            foreach ($result as $index => $event) {

                if (!$event->isBookable()) {
                    $result->removeByIndex($index);
                }
            }
        }

        return $result;
    }

    /**
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getVenues(): \Frootbox\Db\Result
    {
        // Fetch venues
        $venueRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Events\Persistence\Repositories\Venues::class);
        $venues = $venueRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        return $venues;
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
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch event
        $event = $eventRepository->fetchById($this->getAttribute('eventId'));

        return new Response([
            'event' => $event,
        ]);
    }

    /**
     *
     */
    public function showVenueAction (
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venueRepository,
    ): Response
    {
        // Fetch venue
        $venue = $venueRepository->fetchById($this->getAttribute('venueId'));

        return new Response([
            'venue' => $venue,
        ]);
    }


}
