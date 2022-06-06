<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers;

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
    public function onBeforeRendering ( \Frootbox\Admin\View $view, \Frootbox\Http\Get $get, \Frootbox\Persistence\Repositories\ContentElements $contentElements, \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers ) {
        
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));
        
        $result = $teasers->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ]           
        ]);
                
        $view->set('teasers', $result);
    }
}