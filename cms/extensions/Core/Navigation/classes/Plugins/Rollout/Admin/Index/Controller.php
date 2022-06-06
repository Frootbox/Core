<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Rollout\Admin\Index;

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
            'maxDepth' => $post->get('maxDepth'),
            'showHomepageLink' => $post->get('showHomepageLink')
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