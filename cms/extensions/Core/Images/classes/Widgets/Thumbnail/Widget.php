<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\Thumbnail;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
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
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Files $files
    )
    {
        // Fetch file
        $file = $files->fetchByUid($this->getUid('image'), [
            'fallbackLanguageDefault' => true,
        ]);

        $view->set('file', $file);
    }

    /**
     * Cleanup widgets resources before it gets deleted
     */
    public function onBeforeDelete(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        if (!$file = $files->fetchByUid($this->getUid('image'))) {
            return;
        }

        $file->delete();
    }
}
