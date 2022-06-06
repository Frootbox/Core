<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Products\Admin\Index;

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
        $this->plugin->unsetConfig('tags');
        $this->plugin->addConfig([
            'tags' => explode(',', $post->get('tags'))
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
