<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Widgets\Map\Admin;

class Controller extends \Frootbox\Admin\AbstractWidgetController {

    /**
     *
     */
    public function getPath ( ): string
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
        // Validate required fields
        $post->require([ 'lat', 'lng' ]);


        $this->widget->addConfig([
            'lat' => $post->get('lat'),
            'lng' => $post->get('lng'),
            'height' => $post->get('height')
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

