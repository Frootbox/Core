<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\ImageMap\Partials\ListImageMaps;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository
    ): Response
    {
        // Fetch reference
        $reference = $this->getData('reference');

        $result = $imageMapsRepository->fetch([
            'where' => [
                'parentId' => $reference->getId()
            ],
            'order' => [ 'orderId DESC' ],
        ]);

        return new Response('html', 200, [
            'entities' => $result
        ]);
    }
}
