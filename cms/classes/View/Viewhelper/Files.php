<?php
/**
 *
 */

namespace Frootbox\View\Viewhelper;


class Files extends AbstractViewhelper
{
    protected $arguments = [
        'getFileById' => [
            'fileId',
        ],
        'getFilesByUid' => [
            'uid',
            [ 'name' => 'sort', 'default' => 'orderId DESC' ],
        ],
        'getFileByUid' => [
            'uid',
            [ 'name' => 'parameters', 'default' => [] ],
        ],
        'getFolder' => [
            'folderId',
        ],
        'getImageUriByUid' => [
            'uid',
        ],
        'getThumbnail' => [
            'file',
        ],
        'parseFileSize' => [
            'size',
        ]
    ];

    /**
     * @param string $fileId
     * @param \Frootbox\Persistence\Repositories\Files $fileRepository
     * @return \Frootbox\Persistence\File
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function getFileByIdAction(
        string $fileId,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    ): \Frootbox\Persistence\File
    {
        return $fileRepository->fetchById($fileId);
    }

    /**
     * @param string $uid
     * @param array $parameters
     * @param \Frootbox\Persistence\Repositories\Files $fileRepository
     * @return \Frootbox\Persistence\File|null
     */
    public function getFileByUidAction(
        string $uid,
        array $parameters,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    ): ?\Frootbox\Persistence\File
    {
        if (empty($parameters['order'])) {
            $parameters['order'] = 'orderId DESC';
        }

        // Fetch file
        $result = $fileRepository->fetchOne([
            'where' => [
                'uid' => $uid,
            ],
            'order' => [ $parameters['order'] ],
        ]);

        return $result;
    }

    /**
     * @param string $uid
     * @param string $sort
     * @param \Frootbox\Persistence\Repositories\Files $fileRepository
     * @return \Frootbox\Db\Result
     */
    public function getFilesByUidAction(
        string $uid,
        string $sort,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    ): \Frootbox\Db\Result
    {
        $files = $fileRepository->fetchResultByUid($uid, [
            'fallbackLanguageDefault' => true,
            'order' => $sort,
        ]);

        return $files;
    }

    /**
     * @param $folderId
     * @param \Frootbox\Persistence\Repositories\Folders $folders
     * @return \Frootbox\Persistence\Folder
     * @throws \Frootbox\Exceptions\NotFound
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
     * @param string $uid
     * @param \Frootbox\Persistence\Repositories\Files $files
     * @return string|null
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
     * @return float
     */
    public function getUploadMaxSizeAction(): float
    {
        return round(\Frootbox\Persistence\Repositories\Files::getUploadMaxSize() / 1024 / 1024, 2);
    }

    /**
     * @param $size
     * @return string
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
