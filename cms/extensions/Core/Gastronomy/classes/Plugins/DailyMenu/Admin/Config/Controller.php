<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\Config;

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
        // Set new config
        $this->plugin->addConfig([
            'skipWeekend' => $post->get('skipWeekend')
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
