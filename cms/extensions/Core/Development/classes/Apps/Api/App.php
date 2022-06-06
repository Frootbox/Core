<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\Api;

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
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\ConfigStatics $configStatics,
    ): Response
    {
        $token = !empty($post->get('token')) ? $post->get('token') : hash('sha256', md5(microtime(true)));

        $configStatics->addConfig([
            'api' => [
                'token' => $token,
            ],
        ]);

        $configStatics->write();


        d($configStatics);
    }


    /** 
     * 
     */
    public function indexAction (

    )
    {

        return self::getResponse();
    }    
}