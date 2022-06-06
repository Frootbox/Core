<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-28
 */

namespace Frootbox\Admin\Controller\Editor\Widget;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxModalConfig(
        \Frootbox\Persistence\Repositories\Extensions $extensions,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get
    ): Response
    {
        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));
        $view->set('widget', $widget);


        // Fetch available extensions
        $result = $extensions->fetch([
            'where' => [ 'isactive' => 1 ]
        ]);
        $view->set('extensions', $result);

        return self::getResponse('json', 200, [
            'modal' => [
                'html' => self::render()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \DI\Container $container
    ): Response
    {
        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        $view->set('widget', $widget);


        $adminController = $widget->getAdminController();

        $action = $get->get('action') ?? 'index';

        $response = $container->call([ $adminController, $action . 'Action' ]);

        if (empty($response->getBodyData()) and !empty($response->getBody())) {
            return $response;
        }

        if (!empty($response) and $response instanceof \Frootbox\Admin\Controller\Response and $response->getType() == 'json') {
            return $response;
        }

        $viewFile = $adminController->getPath() . 'resources/private/views/Index.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $adminController->getPath() . 'resources/private/views/Index.html';
        }

        $view->set('widgetController', $adminController);

        $payload = !empty($response->getBodyData()) ? $response->getBodyData() : [];

        $html = $view->render($viewFile, null, $payload);

        return self::getResponse('json', 200, [
            'modal' => [
                'html' => $html,
                'size' => $widget->getSize(),
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxCreate(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \DI\Container $container
    ): Response
    {
        // Fetch text
        $text = $texts->fetchByUid($get->get('uid'), [
            'createOnMiss' => true
        ]);

        // Create widget
        $widget = $widgets->insert(new \Frootbox\Persistence\Content\Widget([
            'userId' => '{userId}',
            'textUid' => $get->get('uid'),
            'className' => \Frootbox\Persistence\Content\Widget::class,
            'config' => [
                'width' => 12,
                'align' => 'justify',
            ],
        ]));

        // Update text
        if ($get->get('placement') == 'bottom') {
            $text->setText($text->getText() . PHP_EOL . '<figure data-id="' . $widget->getId() . '"></figure>');
        }
        else {
            $text->setText('<figure data-id="' . $widget->getId() . '"></figure>' . PHP_EOL . $text->getText());
        }

        $text->save();

        $widgetHtml = $container->call([ $widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'id' => $widget->getId(),
                'html' => $widgetHtml
            ]
        ]);
    }

    /**
     * Delete box
     */
    public function ajaxDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \DI\Container $container
    ): Response
    {
        try {

            // Fetch widget
            $widget = $widgets->fetchById($get->get('widgetId'));

            // Call widgets unload method
            if (method_exists($widget, 'unload')) {
                $container->call([$widget, 'unload']);
            }

            // Call widgets onBeforeDelete event
            if (method_exists($widget, 'onBeforeDelete')) {
                $container->call([$widget, 'onBeforeDelete']);
            }

            // Delete box
            $widget->delete();
        }
        catch ( \Frootbox\Exceptions\NotFound $e ) {

            // Ignore error when widget is not found. We assume a prior error
            // and move on trying to clean the tag from the original text to
            // avoid non-active members.
        }


        // Fetch text
        $text = $texts->fetchByUid($get->get('textUid'));

        $textString = str_replace('<figure data-id="' . $get->get('widgetId') . '"></figure>', '', $text->getText());

        $text->setText($textString);
        $text->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxGetLayouts(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets
    ): Response
    {
        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        $className = $get->get('widgetClass');

        if (!empty($className)) {

            if ($className != get_class($widget)) {
                $widget = new $className;
            }

            $layouts = $widget->getLayouts($config);
        }

        $payload = [
            'templates' => [ ]
        ];

        if (!empty($layouts['index'])) {

            foreach ($layouts['index'] as $template) {

                $template->parseConfig();

                $payload['templates'][] = [
                    'title' => $template->getTitle(),
                    'templateId' => $template->getTemplateId(),
                    'active' => $template->isActive()
                ];
            }
        }

        return self::getResponse('json', 200, $payload);
    }

    /**
     *
     */
    public function ajaxRefresh(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \DI\Container $container
    ): Response
    {
        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        // Render widgets html
        $widgetHtml = $container->call([ $widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'id' => $widget->getId(),
                'html' => $widgetHtml
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAlign (
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Http\Get $get
    ): Response
    {
        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        $widget->addConfig([
            'align' => $get->get('align')
        ]);

        $widget->save();

        return self::getResponse('json', 200, [
            'widget' => [
                'align' => $widget->getAlign(),
                'width' => $widget->getWidth()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateConfig (
        \Frootbox\Persistence\Content\Repositories\Widgets $widgets,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \DI\Container $container
    ): Response
    {
        // Validate required input
        $post->require([ 'widgetClass' ]);

        // Fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        // Update widget
        $widget->setClassName($post->get('widgetClass'));
        $widget->addConfig([
            'width' => $post->get('width'),
            'layoutId' => $post->get('layoutId'),
            'margin' => $post->get('margin')
        ]);

        $widget->save();

        // Re-fetch widget
        $widget = $widgets->fetchById($get->get('widgetId'));

        $widgetHtml = $container->call([ $widget, 'renderHtml' ], [
            'action' => 'Index'
        ]);

        return self::getResponse('json', 200, [
            'widget' => [
                'id' => $widget->getId(),
                'html' => $widgetHtml
            ]
        ]);
    }
}
