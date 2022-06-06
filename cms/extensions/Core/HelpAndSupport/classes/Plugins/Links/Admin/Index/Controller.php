<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Index;

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
        $this->plugin->addConfig($post->get('config'));
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function configAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}