<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Viewhelper;

class Testimonials extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getTestimonials' => [

        ],
    ];

    /**
     *
     */
    public function getTestimonialsAction(
        array $params = null,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch testimonials
        $result = $entitiesRepository->fetch();

        return $result;
    }
}
