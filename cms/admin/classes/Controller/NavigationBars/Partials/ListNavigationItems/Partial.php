<?php 
/**
 * 
 */

namespace Frootbox\Admin\Controller\NavigationBars\Partials\ListNavigationItems;

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
    ): Response
    {
        return new Response('html', 200, [

        ]);
    }
}
