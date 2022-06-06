<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Teaser;

class Plugin extends \Frootbox\Persistence\AbstractPlugin {

    /**
     * Get plugins root path
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function getEvents(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        $limit = 10
    ): \Frootbox\Db\Result
    {
        $limit = $this->getConfig('limit') ?? $limit;
        $tags = $this->getConfig('tags');

        // Fetch events
        $sql = 'SELECT
            SQL_CALC_FOUND_ROWS
            COUNT(e.id) as counter,
            e.*,
            l.id as locationId,
            l.title as locationTitle,
            l.street as locationStreet,
            l.streetNumber as locationStreetNumber
        FROM
            ';

        $params = [ ];

        if (!empty($tags)) {
            $sql .= 'tags t,';
        }

        $sql .= '
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND    
            ' . (!empty($this->getConfig('source')) ? ' e.pluginId = ' . $this->getConfig('source')[0] . ' AND ' : '') . '
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            )';

        if (!empty($tags)) {
            $sql .= '
            AND
                (
                ';

            $loop = 0;

            $and = (string) null;

            foreach ($tags as $tag) {

                $sql .= $and . '(
                    t.itemId = e.id AND
                    t.itemClass = e.className AND
                    t.tag = :tag_' . ++$loop . ' 
                )';

                $params[':tag_' . $loop] = $tag;

                $and = ' OR ';
            }

            $sql .= '
                )
                GROUP BY
			        e.id
                HAVING
			        counter = ' . count($tags);
        }
        else {

            $sql .= ' GROUP BY e.id ';
        }

        $sql .= '
        ORDER BY
            e.dateStart ASC
        LIMIT ' . (int) $limit;

        // Fetch events
        $result = $events->fetchByQuery($sql, $params);

        return $result;
    }


    /**
     *
     */
    public function getLimit ( )
    {
        return $this->getConfig('limit') ?? 10;
    }


    /**
     *
     */
    public function getSource ( ) {

        $result = $this->getModel()->fetch([
            'where' => [
                'className' => 'Frootbox\\Ext\\Core\\Events\\Plugins\\Schedule\\Plugin'
            ],
            'limit' => 1
        ]);


        if ($result->getCount() == 0) {
            return null;
        }

        return $result->current();
    }


    /**
     *
     */
    public function indexAction (
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {

    }
}
