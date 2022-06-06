<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Widgets\Mirror\Admin;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractWidgetController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \DI\Container $container
    ): Response
    {
        // Update widget
        $this->widget->addConfig([
            'widgetId' => $post->get('widgetId')
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'id'=> $this->widget->getId(),
                'html' => $widgetHtml
            ]
        ]);
    }

    /**
    *
    */
    public function indexAction (
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets
    ): Response
    {
        // Fetch widgets
        $widgets = $widgets->fetch();

        return self::getResponse('html', 200, [
            'widgets' => $widgets,
        ]);
    }
}
