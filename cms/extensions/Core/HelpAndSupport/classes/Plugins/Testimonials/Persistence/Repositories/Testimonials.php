<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories;

use Frootbox\Persistence\Repositories;

class Testimonials extends Repositories\AbstractAssets
{
    use Repositories\Traits\Tags;

    protected $class = \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Testimonial::class;
}
