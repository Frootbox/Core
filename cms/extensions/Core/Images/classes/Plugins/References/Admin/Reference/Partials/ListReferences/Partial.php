<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Reference\Partials\ListReferences;

use \Frootbox\Admin\Controller\Response;

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
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        switch ($plugin->getConfig('order')) {
            case 'DateDesc':
                $order = [ 'date DESC' ];
                break;

            case 'DateAsc':
                $order = [ 'date ASC' ];
                break;

            case 'Manual':
            default:
                $order = [ 'orderId DESC' ];
                break;
        }

        // Fetch references
        $references = $referencesRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId(),
            ],
            'order' => $order,
        ]);

        return new Response('html', 200, [
            'references' => $references,
        ]);
    }
}
