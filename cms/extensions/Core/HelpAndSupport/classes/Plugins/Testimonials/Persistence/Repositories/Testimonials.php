<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories;

/**
 *
 */
class Testimonials extends \Frootbox\Persistence\Repositories\AbstractAssets
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $class = \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial::class;
}
