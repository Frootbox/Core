<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Remote\Admin;

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

        $post->require([ 'url' ]);


        $this->widget->addConfig([
            'url' => $post->get('url')
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::response('json', 200, [
            'widget' => [
                'html' => $widgetHtml,
                'id'=> $this->widget->getId()
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

