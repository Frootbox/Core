<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Fields\Partials\ListFields;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
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
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    )
    {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $fieldsRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            // 'order' => [ 'dateStart DESC' ]
        ]);
                
        $view->set('fields', $result);
    }
}
