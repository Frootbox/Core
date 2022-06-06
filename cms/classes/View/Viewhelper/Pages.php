<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;

class Pages extends AbstractViewhelper
{
    protected $arguments = [
        'getChildren' => [
            'pageId',
            [ 'name' => 'parameters', 'default' => [ ] ],
        ],
        'getPage' => [
            'pageId',
        ],
        'getTree' => [
            'page',
        ],
    ];

    /**
     *
     */
    public function getChildrenAction(
        $pageId,
        array $parameters,
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {
        $order = $parameters['order'] ?? 'lft ASC';

        // Fetch children
        $result = $pages->fetch([
            'where' => [ 'parentId' => $pageId, 'Visibility' => 'Public' ],
            'order' => [ $order ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getPageAction(
        $pageId,
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {
        return $pages->fetchById($pageId);
    }

    /**
     *
     */
    public function getTopPagesAction(
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {
        // Fetch top pages
        $result = $pages->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'parentId'),
                'Visibility' => 'Public',
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getTreeAction(
        \Frootbox\Persistence\Page $page,
        \Frootbox\Persistence\Repositories\Pages $pages
    ): \Frootbox\Db\Result
    {
        return $pages->getTree($page->getRootId());
    }
}
