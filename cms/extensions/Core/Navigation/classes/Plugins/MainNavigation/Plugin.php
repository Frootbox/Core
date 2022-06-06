<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\MainNavigation;

class Plugin extends \Frootbox\Persistence\AbstractPlugin {

    /**
     *
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     * Show teaser
     */
    public function indexAction (
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {

        $readFromTop = empty($this->getConfig('targetInput'));

        if (!$readFromTop) {

            preg_match('#fbx://page:([0-9]+)$#', $this->getConfig('targetInput'), $match);

            // Build sql
            if (!empty($this->getConfig('showHomepageLink'))) {
                $sql = 'SELECT * FROM pages WHERE (parentId = ' . (int) $match[1] . ' OR id = ' . (int) $match[1] . ') AND visibility != "Hidden" ORDER BY lft ASC';
            } else {
                $sql = 'SELECT * FROM pages WHERE parentId = ' . (int) $match[1] . ' AND visibility != "Hidden" ORDER BY lft ASC';
            }
        }
        else {

            // Build sql
            if (!empty($this->getConfig('showHomepageLink'))) {
                $sql = 'SELECT * FROM pages WHERE (parentId = rootId OR parentId = 0) AND visibility != "Hidden" ORDER BY lft ASC';
            } else {
                $sql = 'SELECT * FROM pages WHERE parentId = rootId AND visibility != "Hidden" ORDER BY lft ASC';
            }
        }

        // Fetch pages
        // TODO: hardkodiertes SQL zurückbauen
        $result = $pages->fetchByQuery($sql);

        $actPage = $this->getPage();

        foreach ($result as $page) {

            if ($readFromTop) {
                $page->setIsActive($actPage->getId() == $page->getId() or ($page->getParentId() > 0 and $actPage->isChildOf($page)));
            }
            else {
                $page->setIsActive($actPage->getId() == $page->getId() or ($page->getId() != $match[1] and $actPage->isChildOf($page)));
            }
        }

        $view->set('pages', $result);
    }
}