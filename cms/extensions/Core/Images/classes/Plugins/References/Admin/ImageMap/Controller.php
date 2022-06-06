<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\ImageMap;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate requried input
        $post->requireOne([ 'title', 'titles' ]);

        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));

        // Insert multiple entities
        if (!empty($post->get('titles'))) {
            $titles = array_reverse(explode("\n", $post->get('titles')));
        }
        else {
            $titles = [ $post->get('title') ];
        }

        foreach ($titles as $title) {

            // Insert new imagemap
            $imageMap = $imageMapsRepository->insert(new \Frootbox\Ext\Core\Images\Plugins\References\Persistence\ImageMap([
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
                'parentId' => $reference->getId(),
                'title' => $title,
                'visibility' => (DEVMODE ? 2 : 1),
            ]));
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#imageMapsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\ImageMap\Partials\ListImageMaps::class, [
                    'plugin' => $this->plugin,
                    'reference' => $reference,
                    'highlight' => $imageMap->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch image-map
        $imageMap = $imageMapsRepository->fetchById($get->get('imageMapId'));

        // Fetch reference
        $reference = $referencesRepository->fetchById($imageMap->getParentId());

        $imageMap->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#imageMapsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\ImageMap\Partials\ListImageMaps::class, [
                    'plugin' => $this->plugin,
                    'reference' => $reference,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $imageMapId) {

            // Fetch imagemap
            $imageMap = $imageMapsRepository->fetchById($imageMapId);
            $imageMap->setOrderId($orderId--);
            $imageMap->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Sortierung wurde gespeichert.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository
    ): Response
    {
        // Validate requried input
        $post->require([ 'title' ]);

        // Fetch entity
        $imageMap = $imageMapsRepository->fetchById($get->get('imageMapId'));

        // Update entity
        $imageMap->setTitle($post->get('title'));
        $imageMap->save();


        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository
    ): Response
    {
        // Fetch image-map
        $imageMap = $imageMapsRepository->fetchById($get->get('imageMapId'));

        return self::getResponse('html', 200, [
            'imageMap' => $imageMap
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }
}
