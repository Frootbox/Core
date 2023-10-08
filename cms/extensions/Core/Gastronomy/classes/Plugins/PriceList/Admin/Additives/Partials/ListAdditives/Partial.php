<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Additives\Partials\ListAdditives;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additiveRepository,
    ): Response
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch additives
        $result = $additiveRepository->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => [ 'orderId ASC' ]
        ]);

        return new Response('html', 200, [
            'additives' => $result,
        ]);
    }
}
