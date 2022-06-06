<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Admin\Configuration;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
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
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noTestimonialDetailPage' => $post->get('noTestimonialDetailPage'),
        ]);

        $this->plugin->save();

        // Update testimonials
        $result = $entitiesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $testimonial) {

            $testimonial->addConfig([
                'noTestimonialDetailPage' => $post->get('noTestimonialDetailPage'),
            ]);

            $testimonial->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
