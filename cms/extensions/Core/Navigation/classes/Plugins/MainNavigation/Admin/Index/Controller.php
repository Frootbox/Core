<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\MainNavigation\Admin\Index;

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
    public function ajaxUpdateAction (
        \Frootbox\Http\Post $post
    )
    {
        $this->plugin->addConfig([
            'showHomepageLink' => $post->get('showHomepageLink'),
            'targetInput' => $post->get('targetInput')
        ]);

        $this->plugin->save();

        return self::response('json');
    }


    /**
     *
     */
    public function indexAction ( )
    {

        return self::response();
    }
}