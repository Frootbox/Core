<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Widgets\ContactGroup\Admin;

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
        // Validate required fields
        $post->require([ 'categoryId' ]);

        $this->widget->addConfig([
            'categoryId' => $post->get('categoryId'),
            'variables' => $post->get('variables')
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'html' => $widgetHtml,
                'id'=> $this->widget->getId()
            ]
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch contact groups
        $result = $categories->fetch([

        ]);

        $view->set('categories', $result);

        return self::getResponse();
    }
}
