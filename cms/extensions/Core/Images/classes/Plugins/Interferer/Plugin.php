<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\Interferer;

use Frootbox\View\Response;

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

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }
}
