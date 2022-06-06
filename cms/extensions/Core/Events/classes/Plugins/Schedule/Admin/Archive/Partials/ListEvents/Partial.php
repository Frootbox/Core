<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Archive\Partials\ListEvents;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    )
    {

        // Fetch events
        $result = $events->fetch([
            'where' => [
                'pluginId' => $this->getData('plugin')->getId()
            ],
            'order' => [ 'dateStart DESC' ]
        ]);

        $view->set('events', $result);
    }
}