<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\SelfPickup\Partials\PickupTimes;

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
    public function onBeforeRendering(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository,
    ): Response
    {
        // Obtain plugin
        $plugin = $this->getData('Plugin');

        // Fetch pickup-times
        $times = $selfPickupTimeRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId(),
            ],
            'order' => [ 'dateStart ASC' ],
        ]);

        return new Response(body: [
            'Times' => $times,
        ]);
    }
}
