<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Widgets\Mirror;

use DI\Container;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRository
    )
    {
        if (empty($this->getConfig('widgetId'))) {
            return 'Kein Widget gewählt zum Spiegeln!';
        }

        // Fetch widget
        $widget = $widgetsRository->fetchById($this->getConfig('widgetId'));

        $html = $container->call([ $widget, 'renderHtml' ], [
            'action' => 'index',
            'editable' => false,
        ]);

        return new \Frootbox\View\Response([
            'html' => $html,
        ]);
    }

    /**
     * Cleanup widgets resources before it gets deleted
     *
     * Dependencies get auto injected.
     */
    public function unload(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {

    }
}
