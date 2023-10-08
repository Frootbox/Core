<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Apps\Export;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /** 
     * 
     */
    public function indexAction(

    )
    {
        return self::getResponse();
    }
}
