<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\MenuTemplates\Partials\ListMenuTemplates;

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
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuTemplates $menuTemplatesRepository
    ): void
    {
        $plugin = $this->getData('plugin');

        // Fetch menu templates
        $menuTemplates = $menuTemplatesRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);

        $view->set('menuTemplates', $menuTemplates);
    }
}
