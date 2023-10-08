<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Widgets\ContactPerson\Admin;

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
    )
    {

        $post->require([ 'contactId' ]);


        $this->widget->addConfig([
            'contactId' => $post->get('contactId')
        ]);

        $this->widget->save();

        $widgetHtml = $container->call([ $this->widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        http_response_code(200);

        header('Content-Type: application/json');

        die(json_encode([
            'widget' => [
                'html' => $widgetHtml,
                'id'=> $this->widget->getId()
            ]
        ]));
    }


    /**
    *
    */
    public function indexAction (
       \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
       \Frootbox\Admin\View $view
    )
    {
        // Fetch contact groups
        $result = $contacts->fetch([
            'order' => [ 'lastName ASC', 'firstName ASC' ],
        ]);

        $view->set('contacts', $result);

        return self::response();
    }
}

