<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Widgets\CallToActionButton\Admin;

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
            'link' => $post->get('link'),
            'label' => $post->get('label'),
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
    public function indexAction(
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

        return self::getResponse();
    }
}
