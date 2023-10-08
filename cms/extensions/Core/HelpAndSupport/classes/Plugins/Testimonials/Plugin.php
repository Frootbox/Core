<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showTestimonial',
    ];

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $testimonialsRepository,
    ): void
    {
        // Fetch testimonials
        $testimonials = $testimonialsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $testimonials->map('delete');
    }

    /**
     *
     */
    public function getTestimonials(
        array $parameters = null,
    ): \Frootbox\Db\Result
    {
        $where = [
            'pluginId' => $this->getId(),
            new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', (IS_EDITOR ? 1 : 2)),
        ];

        if (!empty($parameters['ignore'])) {
            $where[] = new \Frootbox\Db\Conditions\NotEqual('id', $parameters['ignore']);
        }

        // Fetch entities
        $entitiesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials::class);
        $entities = $entitiesRepository->fetch([
            'where' => $where,
            'order' => [ 'dateStart DESC' ],
        ]);

        return $entities;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }

    /**
     * @param Persistence\Repositories\Testimonials $testimonialsRepository
     * @return Response
     */
    public function showTestimonialAction(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $testimonialsRepository,
    ): Response
    {
        // Fetch testimonial
        $testimonial = $testimonialsRepository->fetchById($this->getAttribute('testimonialId'));

        return new \Frootbox\View\Response([
            'testimonial' => $testimonial,
        ]);
    }
}
