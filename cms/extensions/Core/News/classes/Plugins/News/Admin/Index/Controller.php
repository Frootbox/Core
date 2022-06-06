<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Index;

use Frootbox\Admin\Controller\Response;

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
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
