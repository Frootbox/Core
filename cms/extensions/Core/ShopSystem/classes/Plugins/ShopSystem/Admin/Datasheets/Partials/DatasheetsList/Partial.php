<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\DatasheetsList;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
    ): void
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch datasheets
        $result = $datasheets->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => [ 'title' ]
        ]);

        $view->set('datasheets', $result);
    }
}
