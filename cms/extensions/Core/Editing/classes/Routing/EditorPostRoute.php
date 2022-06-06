<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Editing\Routing;

class EditorPostRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#.*?#';
    }

    /**
     *
     */
    public function performRouting(
        \Frootbox\Http\Response $response,
        \Frootbox\Session $session,
        \DI\Container $container,
        \Frootbox\View\Viewhelper\GeneralPurpose $gp
    ): void
    {

        // Inject editing post route when logged in
        if ($session->isLoggedIn()) {

            $pHtml = $gp->renderPartial(\Frootbox\Ext\Core\Editing\Partials\Editor\Partial::class);


            $parser = new \Frootbox\View\HtmlParser($pHtml, $container);
            $container->call([ $parser, 'rewriteTags' ]);
            $pHtml = $parser->getHtml();

            $html = str_replace('</body>', PHP_EOL . PHP_EOL . $pHtml . PHP_EOL . '</body>', $response->getBody());


            $response->setBody($html);
        }
    }
}
