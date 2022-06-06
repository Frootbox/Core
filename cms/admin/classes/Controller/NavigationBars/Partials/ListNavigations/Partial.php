<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\NavigationBars\Partials\ListNavigations;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
    public function onBeforeRendering(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Navigations $navigationsRepository
    ): Response
    {
        // d($config->get('i18n.languages'));

        // Fetch navigations
        $navigations = $navigationsRepository->fetch([
            'order' => [

            ],
        ]);

        return new Response('html', 200, [
            'navigations' => $navigations,
            'config' => $config,
        ]);
    }
}
