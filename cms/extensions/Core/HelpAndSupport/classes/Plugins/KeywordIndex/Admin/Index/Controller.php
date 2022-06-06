<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Index;

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
        d($post);
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::response();
    }
}