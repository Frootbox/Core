<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Folder extends Category {
    
    protected $model = Repositories\Folders::class;
    
    
    /**
     * 
     */
    public function getFiles ( ) {
        
        // Fetch files
        $files = $this->db->getModel(\Frootbox\Persistence\Repositories\Files::class);
        $result = $files->fetch([
            'where' => [ 'folderId' => $this->getId() ]
        ]);
        
        return $result;
    }
}