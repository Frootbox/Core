<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\StaticPages\Download;

class Page
{
    /**
     * 
     */
    public function serve(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchById($get->get('f'));
        
        header('Content-type: ' . $file->getType());
        header('Content-Disposition: attachment; filename="' . $file->getName() . '"');
        
        readfile(FILES_DIR . $file->getPath());
        exit;
    }
}
