<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Utilities\Widgets\Iframe\Admin;

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
    public function ajaxUpdateAction (
        \Frootbox\Http\Post $post,
        \DI\Container $container
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'url' ]);


        $this->widget->addConfig([
            'url' => $post->get('url'),
            'iframe_width' => $post->get('iframe_width'),
            'iframe_height' => $post->get('iframe_height'),
            'autoHeight' => $post->get('autoHeight'),
            'contentMaxWidth' => $post->get('contentMaxWidth'),
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

