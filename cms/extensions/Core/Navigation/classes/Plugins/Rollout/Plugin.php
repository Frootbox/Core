<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Rollout;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getMaxDepth()
    {
        return $this->getConfig('maxDepth') ?? 3;
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
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {
        // Fetch start page
        $page = $pages->fetchById($this->getPageId());

        $view->set('startPage', $page);
    }
}
