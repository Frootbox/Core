<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Plugins\CopyrightFilesList;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
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
    public function getFiles(
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch files
        $result = $filesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\NotEqual('copyright', '')
            ]
        ]);

        return $result;
    }
}
