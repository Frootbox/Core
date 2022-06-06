<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Offcanvas;

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
    public function indexAction(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {
        // Fetch pages
        $pages = $pagesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\NotEqual('visibility', 'Hidden'),
                new \Frootbox\Db\Conditions\NotEqual('visibility', 'Locked'),
                new \Frootbox\Db\Conditions\MatchColumn('parentId', 'rootId'),
            ],
            'order' => [ 'lft ASC' ]
        ]);

        $view->set('pages', $pages);
    }
}
