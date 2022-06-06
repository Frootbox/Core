<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class RobotsTxtRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^robots.txt$#i';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        // Get viewfile path
        $viewFile = $this->getExtensionPath() . 'resources/private/views/robots.txt.twig';

        // Fetch aliases
        $view->set('serverpath', SERVER_PATH_PROTOCOL);

        // Render sitemap xml
        $source = $view->render($viewFile);

        http_response_code(200);
        header('Content-Type: text/plain; charset=UTF-8');
        die($source);
    }
}
