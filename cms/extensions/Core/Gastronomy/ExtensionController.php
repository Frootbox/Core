<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy;

class ExtensionController extends \Frootbox\AbstractExtensionController {
    
    
    /**
     * Get extensions root path
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}