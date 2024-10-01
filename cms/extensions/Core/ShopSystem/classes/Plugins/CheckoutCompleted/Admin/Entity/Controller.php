<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Admin\Entity;

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
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Persistence\Repositories\Entities $entitiesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required fields
        $post->requireOne([ 'title', 'titles' ]);

        // Insert multiple entities
        if (!empty($post->get('titles'))) {
            $titles = array_reverse(explode("\n", $post->get('titles')));
        }
        else {
            $titles = [ $post->get('title') ];
        }

        foreach ($titles as $title) {

            // Insert new entity
            $entity = $entitiesRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Entity([
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
                'title' => $title,
            ]));
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#entitiesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Admin\Property\Partials\ListEntities::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $entity->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Persistence\Repositories\Entities $entitiesRepository
    ): Response
    {
        // Validate requried input
        $post->require([ 'title' ]);

        // Fetch entity
        $entity = $entitiesRepository->fetchById($get->get('entityId'));

        // Update entity
        $entity->setTitle($post->get('title'));
        $entity->save();

        // Set tags
        $entity->setTags($post->get('tags'));

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxModalComposeAction(): Response
    {
        return self::response('plain');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Persistence\Repositories\Entities $entitiesRepository
    ): Response
    {
        // Fetch property
        $entity = $entitiesRepository->fetchById($get->get('entityId'));

        return self::getResponse('html', 200, [
            'entity' => $entity,
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
