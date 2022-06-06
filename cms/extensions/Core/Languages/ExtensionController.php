<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Languages;

class ExtensionController extends \Frootbox\AbstractExtensionController
{
    /**
     * Get extensions root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}
