<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Apps\FileManager;

use Frootbox\Admin\Controller\Response;

class App extends \Frootbox\Admin\Persistence\AbstractApp
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
    public function ajaxFileDeleteAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch file        
        $file = $files->fetchById($get->get('fileId'));
        
        
        // Delete physical file
        $path = $config->get('filesRootFolder') . $file->getPath();
        
        unlink($path);
        
        
        // Delete database record        
        $file->delete();
        
        
        return self::response('json', 200, [
            'replace' => [
                'selector' => '#folderReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\FileManager\Apps\FileManager\Partials\Folder::class, [
                    'folderId' => $file->getFolderId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxFileDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
    )
    {
        // Fetch file
        $file = $fileRepository->fetchById($get->get('fileId'));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '.file-editor',
                'html' => $this->render($view, [
                    'file' => $file,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxFileUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        $file->setTitle($post->get('title'));
        $file->setName($post->get('name'));
        $file->setCopyright($post->get('copyright'));

        $file->addConfig([
            'caption' => $post->get('caption'),
            'alt' => $post->get('alt'),
        ]);

        $file->save();

        return self::getResponse('json', 200, [ ]);
    }
    
    /**
     * 
     */
    public function ajaxFolderCreateAction ( \Frootbox\Http\Post $post, \Frootbox\Http\Get $get, \Frootbox\Persistence\Repositories\Folders $folders, \Frootbox\Admin\Viewhelper\GeneralPurpose $gp ) {
        
        // Fetch parent folder
        $parent = $folders->fetchById($get->get('folderId'));
        
        
        // Append new folder
        $folder = $parent->appendChild(new \Frootbox\Persistence\Folder([
            'title' => $post->get('title')
        ]));
        
        return new \Frootbox\Admin\Controller\Response('redirect', 302, $this->getUri('index', [ 'folderId' => $parent->getId() ]));
         
        
        
        return self::response('json', 200, [
            'replace' => [
                'selector' => '#folderReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\FileManager\Apps\FileManager\Partials\Folder::class, [
                    'highlight' => $folder->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }
    
    
    /**
     * 
     */
    public function ajaxFolderDeleteAction ( \Frootbox\Persistence\Repositories\Folders $folders, \Frootbox\Http\Get $get, \Frootbox\Admin\Viewhelper\GeneralPurpose $gp ) {
        
        // Fetch folder
        $folder = $folders->fetchById($get->get('folderId'));
        
        $folder->delete();
        
        return self::response('json', 200, [
            'replace' => [
                'selector' => '#folderReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\FileManager\Apps\FileManager\Partials\Folder::class, [
                    'folderId' => $folder->getParentId()                  
                ])
            ],
            'modalDismiss' => true
        ]);
    }
    
    
    /**
     * 
     */
    public function ajaxUploadAction(
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch folder
        $folder = $folders->fetchById($get->get('folderId'));

        foreach ($_FILES['files']['name'] as $index => $name) {
                
            // Insert file
            $file = $files->insert(new \Frootbox\Persistence\File([
                'folderId' => $folder->getId(),
                'name' => $_FILES['files']['name'][$index],
                'type' => $_FILES['files']['type'][$index],
                'size' => $_FILES['files']['size'][$index],
                'sourceFile' => $_FILES['files']['tmp_name'][$index],
                'targetPath' => $config->get('filesRootFolder')
            ]));
        }
        
        return new \Frootbox\Admin\Controller\Response('redirect', 302, $this->getUri('index', [ 'folderId' => $folder->getId() ]));        
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Persistence\Repositories\Folders $folders,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    )
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        $info = pathinfo(FILES_DIR . $file->getPath());

        if (in_array($info['extension'], [])) {
            $exif = exif_read_data(FILES_DIR . $file->getPath(), 'APP12', true);
        }
        else {
            $exif = null;
        }

        return self::getResponse('html', 200, [
            'file' => $file,
            'exif' => $exif,
        ]);
    }

    /**
     *
     */
    public function editAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        /*

        d($file);
        $info = pathinfo(FILES_DIR . $file->getPath());

        if (in_array($info['extension'], [])) {
            $exif = exif_read_data(FILES_DIR . $file->getPath(), 'APP12', true);
        }
        else {
            $exif = null;
        }
        */

        return self::getResponse('html', 200, [
            'file' => $file,
        ]);
    }

    /**
     *
     */
    public function galleryAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Folders $folderRepository,
    ): Response
    {
        // Fetch folder
        $folder = $folderRepository->fetchById($get->get('folderId'));

        return new Response(body: [
            'folder' => $folder,
        ]);
    }


    /** 
     * 
     */
    public function indexAction()
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function recoverFileAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        $trashPath = FILES_DIR . 'trash/' . basename($file->getPath());
        $realPath = FILES_DIR . $file->getPath();

        rename($trashPath, $realPath);

        die("OK");
    }

    /**
     *
     */
    public function replaceFileAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): Response
    {
        // Fetch file
        $file = $filesRepository->fetchById($get->get('fileId'));

        if (file_exists(FILES_DIR . $file->getPath())) {
            unlink(FILES_DIR . $file->getPath());
        }

        $file->setName($_FILES['file']['name']);
        $file->setType($_FILES['file']['type']);
        $file->save();

        $path = dirname($file->getPath()) . '/' . $filesRepository::generateFilename($file);

        $file->setPath($path);
        $file->save();

        move_uploaded_file($_FILES['file']['tmp_name'], FILES_DIR . $path);

        die("OK");
    }
}
