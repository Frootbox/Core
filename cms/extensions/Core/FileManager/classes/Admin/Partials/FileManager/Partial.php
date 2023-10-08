<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Admin\Partials\FileManager;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
    public function onBeforeRendering(
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    )
    {
        // Get max upload size
        $maxUploadInBytes = $fileRepository::getUploadMaxSize();

        return new \Frootbox\Admin\Controller\Response('html', 200, [
            'maxUploadInBytes' => $maxUploadInBytes,
            'maxUploadSize' => round($maxUploadInBytes / 1024 / 1024, 2),
        ]);
    }
}