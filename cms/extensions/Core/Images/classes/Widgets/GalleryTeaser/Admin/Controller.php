<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\GalleryTeaser\Admin;

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
        // Validate required input
        $post->require([ 'categoryId' ]);

        // Update widget
        $this->widget->addConfig([
            'categoryId' => $post->get('categoryId')
        ]);

        $this->widget->save();

        // Render widget html for live updating layout
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
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch gallery plugins
        $result = $categories->fetch([

        ]);

        $view->set('categories', $result);

        return self::getResponse();
    }
}

