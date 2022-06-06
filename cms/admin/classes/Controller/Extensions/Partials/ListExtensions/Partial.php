<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\Extensions\Partials\ListExtensions;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Fetch extensions
        $extensions = $extensionsRepository->fetch([
            'order' => [
                'vendorId ASC', 'extensionId ASC'
            ]
        ]);

        return new Response('html', 200, [
            'extensions' => $extensions
        ]);
    }
}
