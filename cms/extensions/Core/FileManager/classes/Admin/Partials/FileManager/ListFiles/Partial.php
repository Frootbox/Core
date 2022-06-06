<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\FileManager\Admin\Partials\FileManager\ListFiles;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getData('uid')
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        $view->set('files', $result);
    }
}