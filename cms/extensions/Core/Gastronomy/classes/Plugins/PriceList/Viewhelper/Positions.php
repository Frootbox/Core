<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Viewhelper;

class Positions extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getByTag' => [
            'tag',
            [ 'name' => 'limit', 'default' => 10 ],
            [ 'name' => 'order', 'default' => 'RAND()' ],
        ],
    ];

    /**
     * @param string $tag
     * @param $limit
     * @param $order
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
     * @param \Frootbox\Session $session
     * @return \Frootbox\Db\Result
     */
    public function getByTagAction(
        string $tag,
        $limit,
        $order,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
        \Frootbox\Session $session
    ): \Frootbox\Db\Result
    {
        $minVisibility = $session->isLoggedIn() ? 1 : 2;

        $tags = [ $tag ];

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
                a.className = "Frootbox\\\\Ext\\\\Core\\\\Gastronomy\\\\Plugins\\\\PriceList\\\\Persistence\\\\ListEntry" AND
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
        $result = $listEntriesRepository->fetchByQuery($sql, $params);

        return $result;
    }
}
