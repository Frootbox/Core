<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\Teaser\Admin\Index;

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
            'source' => $post->get('source'),
            'limit' => $post->get('limit'),
            'tags' => (!empty($post->get('tags')) ? explode(',', $post->get('tags')) : null),
            'maxAgeDays' => $post->get('maxAgeDays'),
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
        // Fetch news plugins
        $result = $plugins->fetch([
            'where' => [ 'className' => 'Frootbox\\Ext\\Core\\News\\Plugins\\News\\Plugin' ]
        ]);

        $view->set('plugins', $result);

        return self::getResponse();
    }
}
