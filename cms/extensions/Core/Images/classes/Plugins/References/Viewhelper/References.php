<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Viewhelper;

class References extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getReferences' => [
            [ 'name' => 'limit', 'default' => 10 ],
            [ 'name' => 'order', 'default' => 'orderId DESC' ],
            [ 'name' => 'parameters', 'default' => [] ],
        ],
        'getReferencesByTags' => [
            'tags',
            [ 'name' => 'limit', 'default' => 10 ],
            [ 'name' => 'order', 'default' => 'RAND()' ],
        ],
        'getAvailableTags' => [
            'parameters',
        ],
    ];

    /**
     * 
     */
    public function getReferencesAction(
        $limit,
        $order,
        array $parameters = null,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): \Frootbox\Db\Result
    {
        $where = [
            new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
        ];

        if (!empty($parameters['pluginId'])) {
            $where['pluginId'] = $parameters['pluginId'];
        }

        if (!empty($parameters['ignoreIds'])) {

            foreach ($parameters['ignoreIds'] as $referenceId) {
                $where[] = new \Frootbox\Db\Conditions\NotEqual('id', $referenceId);
            }
        }

        // Fetch references
        $result = $referencesRepository->fetch([
            'limit' => $limit,
            'order' => [ $order ],
            'where' => $where,
        ]);

        return $result;
    }

    /**
     *
     */
    public function getReferencesByTagsAction(
        $tags,
        $limit,
        $order,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Session $session
    ): \Frootbox\Db\Result
    {
        $minVisibility = $session->isLoggedIn() ? 1 : 2;

        // Fetch events
        $sql = 'SELECT
            SQL_CALC_FOUND_ROWS
            COUNT(a.id) as counter,
            a.*
        FROM
            ';

        $params = [ ];

        if (!empty($tags)) {
            $sql .= 'tags t,';
        }

        $sql .= '
            assets a        
        WHERE
            (
                a.className = "Frootbox\\\\Ext\\\\Core\\\\Images\\\\Plugins\\\\References\\\\Persistence\\\\Reference" AND
                a.visibility >= ' . $minVisibility . ' 
            )
            ';

        if (!empty($ignore)) {

            $sql .= ' AND a.id NOT IN ("' . implode('", "', $ignore) . '") ';
        }

        if (!empty($tags)) {
            $sql .= '
            AND
                (
                ';

            $loop = 0;

            $and = (string) null;

            foreach ($tags as $tag) {

                if (is_object($tag)) {
                    $tag = $tag->getTag();
                }

                $sql .= $and . '(
                    t.itemId = a.id AND
                    t.itemClass = a.className AND
                    t.tag = :tag_' . ++$loop . ' 
                )';

                $params[':tag_' . $loop] = $tag;

                $and = ' OR ';
            }

            $sql .= '
                )
                GROUP BY
			        a.id
                HAVING
			        counter = ' . (is_array($tags) ? count($tags) : $tags->getCount());
        }
        else {
            $sql .= ' GROUP BY a.id ';
        }

        if (!empty($order)) {
            $sql .= ' ORDER BY RAND()';
        }
        else {
            $sql .= ' ORDER BY a.orderId DESC ';
        }

        if (!empty($limit)) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        // Fetch events
        $result = $referencesRepository->fetchByQuery($sql, $params);

        return $result;
    }

    /**
     *
     */
    public function getAvailableTagsAction(
        \Frootbox\Persistence\Repositories\Tags $tagsRepository,
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        $payload = [
            'className' => \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference::class,
        ];

        $sql = 'SELECT 
            COUNT(t.id) as count, 
            t.tag as tag
        FROM
            tags t,
            assets a
        WHERE
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
            t.tag DESC';

        $result = $tagsRepository->fetchByQuery($sql, $payload);

        return $result;
    }
}
