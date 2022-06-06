<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\Slider\Admin\Index;

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
    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}