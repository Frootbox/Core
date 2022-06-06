<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Thumbnail\Admin;

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
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Files $files
    ): \Frootbox\Admin\Controller\Response
    {
        $this->widget->unsetConfig('image');
        $this->widget->unsetConfig('magnify');

        // Update config
        $this->widget->addConfig([
            'image' => $post->get('image'),
            'crop' => $post->get('crop'),
            'caption' => $post->get('caption'),
            'copyright' => $post->get('copyright'),
            'url' => $post->get('url'),
            'alt' => $post->get('alt'),
            'source' => $post->get('source'),
            'magnify' => $post->get('magnify'),
        ]);
        $this->widget->save();

        // Fetch file
        $file = $files->fetchByUid($this->widget->getUid('image'));
        $file->addConfig([
            'caption' => $post->get('caption'),
        ]);
        $file->setCopyright($post->get('copyright'));
        $file->save();

        // Render widget html
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
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($this->widget->getUid('image'));
        $view->set('file', $file);

        return self::getResponse();
    }
}
