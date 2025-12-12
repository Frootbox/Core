<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Admin\Testimonial;

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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required fields
        $post->requireOne([ 'title', 'titles' ]);

        // Insert multiple testimonial
        if (!empty($post->get('titles'))) {
            $titles = array_reverse(explode("\n", $post->get('titles')));
        }
        else {
            $titles = [ $post->get('title') ];
        }

        $date = new \DateTime();
        $date->modify('-' . count($titles) . ' second');

        foreach ($titles as $title) {

            // Insert new testimonial
            $entity = $entitiesRepository->insert(new \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial([
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
                'title' => $title,
                'dateStart' => $date->format('Y-m-d H:i:s'),
                'visibility' => (DEVMODE ? 2 : 1),
                'config' => [
                    'noTestimonialDetailPage' => $this->plugin->getConfig('noTestimonialDetailPage'),
                ],
            ]));

            $date->modify('+1 second');
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#entitiesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Admin\Testimonial\Partials\ListEntities::class, [
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
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch entity
        $entity = $entitiesRepository->fetchById($get->get('entityId'));
        $entity->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#entitiesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Admin\Testimonial\Partials\ListEntities::class, [
                    'plugin' => $this->plugin,
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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
    ): Response
    {
        // Validate requried input
        $post->require([ 'title' ]);

        // Fetch entity
        $entity = $entitiesRepository->fetchById($get->get('entityId'));

        // Update entity
        $entity->setPageId($this->plugin->getPageId());
        $entity->setTitle($post->get('title'));
        $entity->setDateStart($post->get('dateStart') . ' ' . $post->get('timeStart'));

        $entity->addConfig([
            'stars' => $post->get('stars'),
            'noIndividualDetailsPage' => $post->getBoolean('SkipDetailsPage'),
        ]);

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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
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
