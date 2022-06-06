<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Widgets\Downloads;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     * df
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getFiles(
        \Frootbox\Persistence\Repositories\Files $files
    ): \Frootbox\Db\Result
    {

        // Fetch uplaoded files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getuid('files'),
                'language' => $_SESSION['frontend']['language'],
            ],
            'order' => [ 'orderId DESC'],
        ]);

        if ($result->getCount() == 0 and GLOBAL_LANGUAGE != DEFAULT_LANGUAGE) {

            $result = $files->fetch([
                'where' => [
                    'uid' => $this->getuid('files'),
                    'language' => DEFAULT_LANGUAGE,
                ],
                'order' => [ 'orderId DESC'],
            ]);
        }

        return $result;
    }

    /**
     * Cleanup widgets resources before it gets deleted
     */
    public function onBeforeDelete(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch uplaoded files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getuid('files')
            ],
            'order' => [ 'orderId DESC']
        ]);

        $result->map('delete');
    }
}