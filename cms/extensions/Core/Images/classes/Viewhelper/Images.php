<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Viewhelper;

class Images extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getImageByUid' => [
            'uid',
        ],
        'getImagesByUid' => [
            'uid',
        ],
    ];

    /**
     *
     */
    public function getImageByUidAction(
        string $uid,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $imagesRepository,
    ): ?\Frootbox\Ext\Core\Images\Persistence\Image
    {
        // Fetch image
        $result = $imagesRepository->fetchOne([
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
    public function getImagesByUidAction(
        string $uid,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Images $imagesRepository,
    ): \Frootbox\Db\Result
    {
        // Fetch images
        $result = $imagesRepository->fetch([
            'where' => [
                'uid' => $uid,
            ],
            'order' => [ 'orderId DESC' ],
        ]);

        return $result;
    }
}
