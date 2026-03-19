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
     * @param Persistence\Repositories\Testimonials $testimonialsRepository
     * @return void
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
     * @param array|null $parameters
     * @return \Frootbox\Db\Result
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
     * @return Response
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param Persistence\Repositories\Testimonials $testimonialsRepository
     * @return Response
     */
    public function jumpNextAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $testimonialsRepository,
    ): Response
    {
        // Fetch testimonial
        $testimonial = $testimonialsRepository->fetchById($this->getAttribute('testimonialId'));


        $result = $testimonialsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                 new \Frootbox\Db\Conditions\LessOrEqual('date', $testimonial->getDate()),
                new \Frootbox\Db\Conditions\NotEqual('id', $testimonial->getId()),
            ],
            'order' => [ 'date DESC' ],
        ]);

        $next = null;

        /**
         * @var \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial $checkTestimonial
         */
        foreach ($result as $checkTestimonial) {

            if ($checkTestimonial->hasDetailsPage()) {
                $next = $checkTestimonial;
                break;
            }
        }

        if ($next === null) {

            $result = $testimonialsRepository->fetch([
                'where' => [
                    'pluginId' => $this->getId(),
                    new \Frootbox\Db\Conditions\NotEqual('id', $testimonial->getId()),
                ],
                'order' => [ 'date DESC' ],
            ]);

            /**
             * @var \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial $checkTestimonial
             */
            foreach ($result as $checkTestimonial) {

                if ($checkTestimonial->hasDetailsPage()) {
                    $next = $checkTestimonial;
                    break;
                }
            }

            if ($next === null) {
                return new \Frootbox\View\ResponseRedirect($this->getUri());
            }
        }

        return new \Frootbox\View\ResponseRedirect($next->getUri());
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
