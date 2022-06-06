<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Link\Partials\ListLinks;

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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links
    )
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch links
        $result = $links->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => $plugin->getSorting(),
        ]);

        $view->set('links', $result);
    }
}