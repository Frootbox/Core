<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Plugins\Gallery\Admin\Index;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalImportAction(

    ): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function exportAction(
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
    ): Response
    {
        $exportData = [
            'videos' => [],
        ];

        // Fetch videos
        $result = $videosRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId()
            ],
            'order' => [ 'date DESC', 'id DESC' ]
        ]);

        foreach ($result as $video) {

            $exportData['videos'][] = $video->getData();
        }

        die(json_encode($exportData));
    }

    /**
     *
     */
    public function importAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Video\Persistence\Repositories\Videos $videosRepository,
    ): Response
    {
        // Clear videos
        $result = $videosRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $result->map('delete');

        // Prepare import
        $importData = json_decode($post->get('Json'), true);

        foreach ($importData['videos'] as $videoData) {

            // Clear data
            unset($videoData['id'], $videoData['alias']);

            $videoData['pageId'] = $this->plugin->getPageId();
            $videoData['pluginId'] = $this->plugin->getId();

            // Compose new video
            $video = new \Frootbox\Ext\Core\Video\Persistence\Video($videoData);

            // Persist new video
            $videosRepository->persist($video);
            $video->save();
        }

        d("IMPORT COMPLETE");

        return self::getResponse();
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }
}
