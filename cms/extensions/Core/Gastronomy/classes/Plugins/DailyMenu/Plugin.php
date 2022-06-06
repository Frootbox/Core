<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {

    }
}
