<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Widgets\Video\Admin;

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
            'url' => $post->get('url'),
            'maxWidth' => $post->get('maxWidth'),
            'maxWHeight' => $post->get('maxWHeight'),
            'privacyGuard' => $post->get('privacyGuard'),
            'loop' => !empty($post->get('loop')),
            'muted' => !empty($post->get('muted')),
            'autoplay' => !empty($post->get('autoplay')),
            'source' => $post->get('source'),
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
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
