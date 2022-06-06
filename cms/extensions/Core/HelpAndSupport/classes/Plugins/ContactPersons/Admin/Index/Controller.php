<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController {

    /**
     *
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function indexAction ( )
    {

        return self::response();
    }
}