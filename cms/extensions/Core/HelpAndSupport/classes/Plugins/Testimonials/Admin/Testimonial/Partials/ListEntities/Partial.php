<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Admin\Testimonial\Partials\ListEntities;

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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
    )
    {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $entitiesRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ 'dateStart DESC' ]
        ]);

        return new \Frootbox\Admin\Controller\Response('html', 200, [
            'entities' => $result
        ]);
    }
}
