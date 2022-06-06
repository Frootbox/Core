<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Widgets\Downloads\Admin;

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
    )
    {
        $this->widget->unsetConfig('withHeadline');
        $this->widget->unsetConfig('variables');

        // Update widget
        $this->widget->addConfig([
            'title' => $post->get('title'),
            'withHeadline' => !empty($post->get('withHeadline')),
            'variables' => $post->get('variables'),
        ]);

        $this->widget->save();

        // Render widget layout update
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

    ): \Frootbox\Admin\Controller\Response
    {
        return self::response();
    }
}
