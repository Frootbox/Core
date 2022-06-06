<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Manufacturers\Partials\ManufacturersList;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): void
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch manufacturers
        $result = $manufacturersRepository->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => [ 'title' ]
        ]);

        $view->set('manufacturers', $result);
    }
}
