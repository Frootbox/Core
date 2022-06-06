<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\Teaser;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getArticles(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        $limit = null,
        array $ignore = null
    ): \Frootbox\Db\Result
    {
        $limit = $limit ?? $this->getArticlesLimit();
        $tags = $this->getConfig('tags');

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
            a.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND         
            ' . (!empty($this->getConfig('source')) ? ' a.pluginId = ' . $this->getConfig('source')[0] . ' AND ' : '') . '
            a.className = "Frootbox\\\\Ext\\\\Core\\\\News\\\\Persistence\\\\Article"
            ';

        if (!empty($ignore)) {
            $sql .= ' AND a.id NOT IN ("' . implode('", "', $ignore) . '") ';
        }

        if (!empty($maxDays = $this->getConfig('maxAgeDays'))) {

            $date = new \DateTime();
            $date->modify('-' . (int) $maxDays . ' day');

            $sql .= ' AND a.dateStart >= "' .  $date->format('Y-m-d') . '" ';

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

        $sql .= '
        ORDER BY
            a.dateStart DESC
        LIMIT ' . $limit;


        if (!empty($tags)) {
         #   p($params);
          #  d($sql);
        }

        // Fetch events
        $result = $articles->fetchByQuery($sql, $params);

        return $result;
    }

    /**
     *
     */
    public function getArticlesLimit(): int
    {
        return (int) ($this->getConfig('limit') ?? 10);

    }


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
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {

    }
}
