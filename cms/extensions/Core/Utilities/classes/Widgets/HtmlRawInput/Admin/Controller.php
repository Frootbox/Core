<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Utilities\Widgets\HtmlRawInput\Admin;

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
    ): \Frootbox\Admin\Controller\Response
    {
        // Update widget
        $this->widget->addConfig([
            'source' => $post->get('source')
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::response('json', 200, [
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
       //  \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
       \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {

        /*
        // Fetch contact groups
        $result = $categories->fetch([

        ]);

        $view->set('categories', $result);
        */

        return self::response();
    }
}

