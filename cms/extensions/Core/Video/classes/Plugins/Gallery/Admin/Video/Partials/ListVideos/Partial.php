<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Video\Plugins\Gallery\Admin\Video\Partials\ListVideos;

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
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository
    ): void
    {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $videosRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ 'date DESC', 'id DESC' ]
        ]);

        $view->set('videos', $result);
    }
}
