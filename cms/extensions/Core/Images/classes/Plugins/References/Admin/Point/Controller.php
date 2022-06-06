<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Point;

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
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\ImageMaps $imageMapsRepository,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Points  $pointsRepository
    ): Response
    {
        // Fetch image map
        $imageMap = $imageMapsRepository->fetchById($get->get('imageMapId'));

        // Insert new point
        $point = $pointsRepository->insert(new \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Point([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'parentId' => $imageMap->getId(),
            'config' => [
                'position' => [
                    'x' => $get->get('posX'),
                    'y' => $get->get('posY'),
                ],
            ],
        ]));

        return self::getResponse('json', 200, [
            'point' => [
                'id' => $point->getId(),
            ],
            'editUrl' => $this->plugin->getAdminUri('Point', 'ajaxModalEdit', [ 'pointId' => $point->getId() ]),
        ]);

    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Holl\Kinderland\Plugins\Playspaces\Persistence\Repositories\Points $pointsRepository
    ): Response
    {
        // Fetch point
        $point = $pointsRepository->fetchById($get->get('pointId'));

        $point->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => '[data-point="' . $point->getId() . '"]',
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Holl\Kinderland\Plugins\Playspaces\Persistence\Repositories\Points $pointsRepository
    ): Response
    {
        // Fetch point
        $point = $pointsRepository->fetchById($get->get('pointId'));

        return self::getResponse('plain', 200, [
            'point' => $point,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Holl\Kinderland\Plugins\Playspaces\Persistence\Repositories\Points $pointsRepository
    ): Response
    {
        // Fetch point
        $point = $pointsRepository->fetchById($get->get('pointId'));
        $point->setTitle($post->get('title'));

        // Clean url
        $url = str_replace(SERVER_PATH_PROTOCOL, '', $post->get('url'));

        if (SERVER_PATH != '/') {
            $url = str_replace(SERVER_PATH, '', $url);
        }

        if (substr($url, 0, 5) == 'edit/') {
            $url = substr($url, 5);
        }

        $point->addConfig([
            'url' => $url,
        ]);

        $point->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
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
