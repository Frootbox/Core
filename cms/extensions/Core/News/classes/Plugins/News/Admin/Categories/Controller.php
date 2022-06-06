<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Categories;

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
    public function indexAction()
    {
        return self::getResponse();
    }
}
