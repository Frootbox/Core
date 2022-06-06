<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives\Partials\ListAdditives;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    )
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch additives
        $result = $additivesRepository->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => [ 'orderId ASC' ]
        ]);

        $view->set('additives', $result);
    }
}
