<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\ReferencesTeaser;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getReferences(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Session $session
    ): \Frootbox\Db\Result
    {
        $tags = array_filter($this->getConfig('tags') ?? []);

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
            ' . (!empty($this->getConfig('source')) ? ' a.pluginId = ' . $this->getConfig('source')[0] . ' AND ' : '') . '
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
			        counter = ' . count($tags);
        }
        else {
            $sql .= ' GROUP BY a.id ';
        }


        if (!empty($this->getConfig('order'))) {

            switch ($this->getConfig('order')) {

                case 'newestFirst':
                    $sql .= '
                        ORDER BY
                    a.date DESC';
                    break;

                case 'dateDesc':
                    $sql .= '
                        ORDER BY
                    a.dateStart DESC';
                    break;

                case 'manualOrder':
                    $sql .= '
                        ORDER BY
                    a.orderId DESC';
                    break;

                case 'random':
                    $sql .= '
                        ORDER BY
                    RAND()';
                    break;
            }
        }

        if (!empty($this->getConfig('limit'))) {
            $sql .= ' LIMIT ' . (int) $this->getConfig('limit');
        }

        // Fetch events
        $result = $referencesRepository->fetchByQuery($sql, $params);

        return $result;
    }


    /**
     *
     */
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {

    }
}
