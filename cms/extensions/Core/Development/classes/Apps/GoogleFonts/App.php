<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Development\Apps\GoogleFonts;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxImportAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $data = $post->get('fontData');

        preg_match('#href="(.*?)"#', $data, $match);

        $source = file_get_contents($match[1]);



        d($source);
    }


    /** 
     * 
     */
    public function indexAction (

    ): Response
    {
        return self::getResponse();
    }    
}