<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Archive;

use Frootbox\Admin\View;

class Controller extends \Frootbox\Admin\AbstractPluginController
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
    public function indexAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    )
    {

        // Fetch events
        $result = $events->fetch([

        ]);

        $view->set('events', $result);

        return self::response();
    }

}