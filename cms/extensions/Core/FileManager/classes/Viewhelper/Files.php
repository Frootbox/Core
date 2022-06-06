<?php
/**
 *
 */


namespace Frootbox\Ext\Core\FileManager\Viewhelper;

class Files extends \Frootbox\View\Viewhelper\AbstractViewhelper {

    protected $arguments = [
        'getFolder' => [
            'folderId'
        ],
        'getThumbnail' => [
            'file'
        ]
    ];


    /**
     *
     */
    public function getFolderAction ( $folderId, \Frootbox\Persistence\Repositories\Folders $folders ) {

        return $folders->fetchById($folderId);        
    }
}