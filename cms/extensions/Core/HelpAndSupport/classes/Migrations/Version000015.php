<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000015 extends \Frootbox\AbstractMigration
{
    protected $description = 'Räumt alte Testimonials auf.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $testimonialsRepository,
    ): void
    {
        // Fetch testimonials
        $testimonials = $testimonialsRepository->fetch();

        foreach ($testimonials as $testimonial) {

            try {
                $testimonial->getPlugin();
            }
            catch (\Frootbox\Exceptions\NotFound $e) {

                $testimonial->delete();
            }
        }
    }
}
