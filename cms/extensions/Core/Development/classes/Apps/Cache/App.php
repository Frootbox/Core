<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Cache;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function ajaxClearCacheAction(
        \Frootbox\CacheControl $cacheControl
    ): \Frootbox\Admin\Controller\Response
    {
        $cacheControl->clear();

        return self::response('json', 200, [
            'success' => 'Der Cache wurde geleert.'
        ]);
    }


    /** 
     * 
     */
    public function indexAction (

    )
    {



        return self::response();
    }    
}
