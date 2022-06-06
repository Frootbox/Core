<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Files extends AbstractViewhelper
{
    protected $arguments = [
        'getFilesByUid' => [
            'uid'
        ],
        'getFileByUid' => [
            'uid'
        ],
        'getFolder' => [
            'folderId'
        ],
        'getImageUriByUid' => [
            'uid'
        ],
        'getThumbnail' => [
            'file'
        ],
        'parseFileSize' => [
            'size'
        ]
    ];

    /**
     *
     */
    public function getFileByUidAction(
        string $uid,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        $result = $files->fetchOne([
            'where' => [
                'uid' => $uid,
            ],
            'order' => [ 'orderId DESC' ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getFilesByUidAction(
        string $uid,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        $result = $files->fetch([
            'where' => [
                'uid' => $uid,
            ],
            'order' => [ 'orderId DESC' ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getFolderAction(
        $folderId,
        \Frootbox\Persistence\Repositories\Folders $folders
    ): \Frootbox\Persistence\Folder
    {
        // Fetch folder
        return $folders->fetchById($folderId);        
    }

    /**
     *
     */
    public function getImageUriByUidAction(
        string $uid,
        \Frootbox\Persistence\Repositories\Files $files
    ): ?string
    {
        $file = $files->fetchByUid($uid);

        if (!$file) {
            return null;
        }

        return $file->getUriThumbnail();
    }

    /**
     *
     */
    public function getUploadMaxSizeAction(): float
    {
        return round(\Frootbox\Persistence\Repositories\Files::getUploadMaxSize() / 1024 / 1024, 2);
    }

    /**
     *
     */
    public function parseFileSizeAction(
        $size
    )
    {
        if ($size > (1000 * 1000)) {
            return round($size / (1000 * 1000), 1) . ' M';
        }
        else {
            return round($size / 1000, 1) . ' K';
        }
    }
}
