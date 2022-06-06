<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Plugins\Gallery;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showVideo',
    ];

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videoRepository,
    ): void
    {
        // Fetch videos
        $videos = $videoRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $videos->map('delete');
    }

    /**
     *
     */
    public function getVideos(
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository
    ): \Frootbox\Db\Result
    {
        $result = $videosRepository->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => [ 'date DESC', 'id DESC' ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }

    /**
     *
     */
    public function showVideoAction(
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository
    ): Response
    {
        // Fetch video
        $video = $videosRepository->fetchById($this->getAttribute('videoId'));

        return new \Frootbox\View\Response([
            'video' => $video
        ]);
    }
}
