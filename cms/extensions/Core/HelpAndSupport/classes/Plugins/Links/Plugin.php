<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getAvailableTags(array $parameters = null): \Frootbox\Db\Result
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Tags::class);

        $payload = [
            'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Link::class,
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
            t.tag ASC';

        $result = $tagsRepository->fetchByQuery($sql, $payload);

        return $result;
    }

    /**
     *
     */
    public function getLinks(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links
    )
    {
        // Fetch links
        $result = $links->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => $this->getSorting(),
        ]);

        return $result;
    }

    /**
     *
     */
    public function getLinksByTag(
        string $tag,
        array $parameters = null,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $linksRepository,
    ): \Frootbox\Db\Result
    {
        // Fetch links
        $references = $linksRepository->fetchByTag($tag, $parameters);

        return $references;
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
    public function getSorting(): array
    {
        // Get sorting
        switch ($this->getConfig('sorting')) {

            case 'NewestFirst':
                return [ 'date DESC' ];

            case 'OldestFirst':
                return [ 'date ASC' ];

            case 'Manual':
                return [ 'orderId DESC' ];

            case 'Auto':
            default:
                return [ 'title ASC' ];
        }
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
