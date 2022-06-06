<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController {
    
    /**
     *
     */
    public function getPath ( ) : string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    
    /**
     * 
     */
    public function indexAction ( ) {
                        
        return self::response();
    }
}