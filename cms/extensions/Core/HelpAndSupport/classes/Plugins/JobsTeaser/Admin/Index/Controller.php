<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\JobsTeaser\Admin\Index;

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
        \Frootbox\Http\Post $post,
    ): Response
    {
        $this->plugin->addConfig([
            'limit' => $post->get('limit'),
        ]);

        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }
}
