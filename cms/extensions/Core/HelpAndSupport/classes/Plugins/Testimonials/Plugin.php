<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
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
    public function getTestimonials(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Testimonials\Persistence\Repositories\Testimonials $entitiesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch entities
        $entities = $entitiesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
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
}
