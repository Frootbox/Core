<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Apps\FileManager\Partials\Folder;

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
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Folders $foldersRepository,
        \Frootbox\Config\Config $config
    )
    {
        // Get folder-ID
        if (empty($this->data['folderId'])) {

            $folder = $foldersRepository->fetchOne([
                'where' => [
                    'title' => 'Files Index',
                ],
            ]);

            if (empty($folder)) {

                $folder = $foldersRepository->insertRoot(new \Frootbox\Persistence\Folder([
                    'title' => 'Files Index'
                ]));
            }
        }
        else {
            $folder = $foldersRepository->fetchById($this->data['folderId']);
        }
        
        $view->set('folder', $folder);
    }
}
