<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\SubNavigation;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    use \Frootbox\Persistence\Traits\Uid;

    /**
     *
     */
    public function getPages(
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {
        if (!empty($this->config['pageId'])) {
            
            // Fetch pages
            $result = $pages->fetch([
                'where' => [
                    'parentId' => $this->config['pageId'],
                    new \Frootbox\Db\Conditions\NotEqual('visibility', 'Hidden')
                ],
                'order' => [ 'lft ASC' ]
            ]);
        }
        else {
                    
            // Fetch pages
            $result = $pages->fetch([
                'where' => [
                    'parentId' => $this->page->getId(),
                    new \Frootbox\Db\Conditions\NotEqual('visibility', 'Hidden')
                ],
                'order' => [ 'lft ASC' ]
            ]);

            if ($result->getCount() == 0) {
                
                $result = $pages->fetch([
                    'where' => [
                        'parentId' => $this->page->getParentId(),
                        new \Frootbox\Db\Conditions\NotEqual('visibility', 'Hidden')
                    ],
                    'order' => [ 'lft ASC' ]
                ]);

                if (!empty($this->getConfig('showParentLink'))) {
                    $result->unshift($this->getPage()->getParent());
                }
            }
            elseif (!empty($this->getConfig('showParentLink'))) {
                $result->unshift($this->getPage());
            }
        }

        return $result;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * Show teaser
     */
    public function showTeaserAction(
        Persistence\Repositories\Teasers $teasers,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        // Fetch teaser
        $teaser = $teasers->fetchById($this->getAttribute('teaserId'));

        $view->set('teaser', $teaser);
    }
}
