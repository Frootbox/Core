<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Editor\Partials\FileManager;

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
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Admin\View $view
    ): void
    {
        // Fetch files
        $result = $files->fetch([
            'where' => [
                'uid' => $this->getData('uid'),
                'language' => $_SESSION['frontend']['language'],
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        $view->set('files', $result);
    }
}
