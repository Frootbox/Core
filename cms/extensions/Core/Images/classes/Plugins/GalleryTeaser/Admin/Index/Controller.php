<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\GalleryTeaser\Admin\Index;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): \Frootbox\Admin\Controller\Response
    {
        $this->plugin->addConfig([
            'categoryId' => $post->get('categoryId')
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
