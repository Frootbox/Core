<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Plugins\Gallery\Admin\Video;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
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
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required fields
        $post->require([ 'title' ]);

        // Insert new asset
        $video = $videosRepository->insert(new \Frootbox\Ext\Core\Video\Persistence\Video([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title'),
            'config' => [
                'url' => $post->get('url')
            ]
        ]));


        if (!empty($post->get('date'))) {

            $date = new \Frootbox\Dates\Date($post->get('date'));

            $video->setDate($date->format('%Y-%m-%d'));
            $video->save();
        }



        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#videosReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Video\Plugins\Gallery\Admin\Video\Partials\ListVideos::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $video->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch video
        $video = $videosRepository->fetchById($get->get('videoId'));

        $video->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#videosReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Video\Plugins\Gallery\Admin\Video\Partials\ListVideos::class, [
                    'plugin' => $this->plugin
                ])
            ],
            'success' => 'Das Video wurde gelöscht.'
        ]);
    }

    /**
     *
     */
    public function ajaxGrabDataAction(
        \Frootbox\Http\Get $get
    ): Response
    {
        $source = file_get_contents($get->get('url'));

        $data = [
            'title' => null,
            'date' => null
        ];

        if (preg_match('#<title>(.*?)</title>#i', $source, $match)) {

            $title = htmlspecialchars_decode(trim($match[1]));

            if (substr($title, -10) == ' - YouTube') {
                $title = substr($title, 0, -10);
            }

            $data['title'] = $title;
        }

        if (preg_match('#<meta itemprop="datePublished" content="(.*?)">#i', $source, $match)) {

            $date = new \Frootbox\Dates\Date($match[1]);
            $data['date'] = $date->format('%d.%m.%Y');
        }

        return self::getResponse('json', 200, [
            'data' => $data
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
    ): Response
    {
        // Validate requried input
        $post->require([ 'title' ]);

        // Fetch video
        $video = $videosRepository->fetchById($get->get('videoId'));

        // Update video
        $video->setTitle($post->get('title'));
        $video->setDate($post->get('dateStart') . ' ' . $post->get('timeStart'));
        $video->addConfig([
            'url' => $post->get('url'),
        ]);

        $video->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
    ): Response
    {
        // Fetch video
        $video = $videosRepository->fetchById($get->get('videoId'));

        return self::getResponse('html', 200, [
            'video' => $video,
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