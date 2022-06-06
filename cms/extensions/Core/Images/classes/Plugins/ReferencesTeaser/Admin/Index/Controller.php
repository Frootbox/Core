<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\ReferencesTeaser\Admin\Index;

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
        $this->plugin->unsetConfig('limit');
        $this->plugin->unsetConfig('order');

        $this->plugin->addConfig([
            'tags' => explode(',', $post->get('tags')),
            'limit' => $post->get('limit'),
            'order' => $post->get('order'),
        ]);

        $this->plugin->save();

        return self::getResponse('json', 200);
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
