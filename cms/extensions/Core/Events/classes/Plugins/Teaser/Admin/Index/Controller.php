<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Teaser\Admin\Index;

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
        // Update config
        $this->plugin->unsetConfig('tags');
        $this->plugin->unsetConfig('source');
        $this->plugin->addConfig([
            'limit' => $post->get('limit'),
            'source' => $post->get('source'),
            'tags' => (!empty($post->get('tags')) ? explode(',', $post->get('tags')) : null)
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\ContentElements $plugins
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch events plugins
        $result = $plugins->fetch([
            'where' => [ 'className' => \Frootbox\Ext\Core\Events\Plugins\Schedule\Plugin::class ]
        ]);

        $view->set('plugins', $result);

        return self::getResponse();
    }
}
