<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Editor\Partials\FileUpload;

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
        \Frootbox\Admin\View $view,
    ): \Frootbox\Admin\Controller\Response
    {
        $file = $fileRepository->fetchOne([
            'where' => [
                'uid' => $this->getData('uid'),
            ],
        ]);

        // Get max upload size
        $maxUploadInBytes = $fileRepository::getUploadMaxSize();

        return new \Frootbox\Admin\Controller\Response('html', 200, [
            'File' => $file,
            'maxUploadInBytes' => $maxUploadInBytes,
            'maxUploadSize' => round($maxUploadInBytes / 1024 / 1024, 2),
        ]);
    }
}
