<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\NextPreviousOverview;

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
