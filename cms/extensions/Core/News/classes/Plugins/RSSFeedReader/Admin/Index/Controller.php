<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\RSSFeedReader\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController {

    /**
     * Get controllers root path
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function ajaxUpdateAction (
        \Frootbox\Http\Post $post
    )
    {

        $this->plugin->addConfig([
            'feedUri' => $post->get('feedUri')
        ]);
        $this->plugin->save();

        return self::response('json');
    }


    /**
     *
     */
    public function indexAction (

    ): \Frootbox\Admin\Controller\Response
    {

        return self::response();
    }
}