<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\PhpInfo;

class App extends \Frootbox\Admin\Persistence\AbstractApp {

    protected $onInsertDefault = [
        'menuId' => 'Frootbox\\Ext\\Core\\Development'
    ];


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
    public function indexAction (
        \Frootbox\Admin\View $view
    )
    {
        return self::getResponse();
    }


    /**
     *
     */
    public function phpinfoAction ( )
    {
        phpinfo();
        exit;
    }
}