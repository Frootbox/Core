<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Gallery\Admin;

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
        $this->widget->addConfig([
            'imageWidth' => ((int) $post->get('width') == 0) ? null : $post->get('width'),
            'imageHeight' => ((int) $post->get('height') == 0) ? null : $post->get('height'),
            'imageColumns' => $post->get('columns'),
            'noMagnifier' => $post->get('noMagnifier'),
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index',
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'id'=> $this->widget->getId(),
                'html' => $widgetHtml,
            ],
        ]);
    }


    /**
    *
    */
    public function indexAction (
       //  \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
       \Frootbox\Admin\View $view
    ): Response
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

