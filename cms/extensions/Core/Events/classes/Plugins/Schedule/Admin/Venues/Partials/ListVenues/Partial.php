<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Venues\Partials\ListVenues;

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
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch venues
        $result = $venues->fetch([
            'where' => [
                'pluginId' => $this->getData('plugin')->getId()
            ]
        ]);

        return new \Frootbox\Admin\Controller\Response(body: [
            'venues' => $result,
        ]);
    }
}
