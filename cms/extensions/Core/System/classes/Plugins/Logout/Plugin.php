<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Plugins\Logout;

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
        unset($_SESSION['user']);

        return new \Frootbox\View\Response([

        ]);
    }
}
