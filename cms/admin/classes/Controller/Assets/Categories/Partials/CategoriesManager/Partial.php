<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Assets\Categories\Partials\CategoriesManager;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {
    
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Categories $categories
    ): void
    {
        // Fetch root category
        $result = $categories->fetch([
            'where' => [
                'uid' => $this->getData('uid'),
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId')
            ]
        ]);

        // Insert root
        if ($result->getCount() == 0) {

            $record = [
                'title' => 'Index',
                'className' => $this->getData('className'),
                'uid' => $this->getData('uid'),
                'visibility' => (DEVMODE ? 2 : 1),
            ];
            
            if ($this->hasData('plugin')) {
                
                $plugin = $this->getData('plugin');
                
                $record['pageId'] = $plugin->getPageId();
                $record['pluginId'] = $plugin->getId();
            }
                        
            $root = $categories->insertRoot(new \Frootbox\Persistence\Category($record));
        }
        else {

            $root = $result->current();
        }

        $tree = $categories->getTree($root->getId());

        $view->set('tree', $tree);
    }
}