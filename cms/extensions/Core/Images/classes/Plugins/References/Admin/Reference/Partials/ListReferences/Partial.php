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
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referenceRepository,
    ): Response
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        switch ($plugin->getConfig('order')) {
            case 'NewestFirst':
                $order = [ 'date DESC' ];
                break;

            case 'DateAsc':
                $order = [ 'date ASC' ];
                break;

            case 'DateDesc':
                $order = [ 'dateStart DESC' ];
                break;

            case 'Manual':
            default:
                $order = [ 'orderId DESC' ];
                break;
        }

        if ($this->hasData('keyword')) {

            // Build sql
            $sql = 'SELECT 
                *
            FROM
                assets 
            WHERE
                className = :className AND
                title LIKE :keyword
            ';

            // Fetch references
            $references = $referenceRepository->fetchByQuery($sql, [
                'className' => \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference::class,
                'keyword' => '%' . $this->getData('keyword') . '%',
            ]);
        }
        else {

            // Fetch references
            $references = $referenceRepository->fetch([
                'where' => [
                    'pluginId' => $plugin->getId(),
                ],
                'order' => $order,
            ]);
        }

        return new Response('html', 200, [
            'references' => $references,
        ]);
    }
}
