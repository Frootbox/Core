<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Plugins;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function indexAction (
        \Frootbox\Persistence\Content\Repositories\ContentElements $pluginRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch plugins
        $plugins = $pluginRepository->fetch([
            'order' => [ 'pageId ASC', 'orderId DESC' ],
        ]);

        return self::getResponse(body: [
            'plugins' => $plugins,
        ]);
    }    
}
