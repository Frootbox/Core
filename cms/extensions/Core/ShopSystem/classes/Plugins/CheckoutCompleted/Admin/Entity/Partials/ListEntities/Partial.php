<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Admin\Entity\Partials\ListEntities;

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
        \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Persistence\Repositories\Entities $entitiesRepository
    )
    {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $entitiesRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ 'date DESC' ]
        ]);

        return new \Frootbox\Admin\Controller\Response('html', 200, [
            'entities' => $result
        ]);
    }
}
