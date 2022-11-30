<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class SitemapRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^sitemap.(xml|txt)$#i';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        $target = $this->request->getRequestTarget();

        // Fetch aliasses
        $result = $aliasesRepository->fetch([
            'where' => [
                'status' => 200,
                'visibility' => 2,
            ],
        ]);

        $view->set('aliases', $result);

        if (substr($target, -4) == '.txt') {

            // Get viewfile path
            $viewFile = $this->getExtensionPath() . 'resources/private/views/sitemap.txt.twig';

            // Render sitemap xml
            $source = $view->render($viewFile);

            http_response_code(200);
            header('Content-Type: text/plain; charset=UTF-8');
            die($source);
        }
        else {

            // Get viewfile path
            $viewFile = $this->getExtensionPath() . 'resources/private/views/sitemap.xml.twig';

            // Render sitemap xml
            $source = $view->render($viewFile);

            http_response_code(200);
            header('Content-Type: text/xml; charset=UTF-8');
            die($source);
        }
    }
}
