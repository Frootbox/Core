<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Plugins\Login\Admin\Index;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->plugin->addConfig([
            'pageId' => $post->get('pageId'),
        ]);

        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): Response
    {
        // Fetch pages
        $pages = $pagesRepository->fetch([
            'order' => [ 'lft ASC' ]
        ]);

        return self::getResponse('html', 200, [
            'pages' => $pages,
        ]);
    }
}
