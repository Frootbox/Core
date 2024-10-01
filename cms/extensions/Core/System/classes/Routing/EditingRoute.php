<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class EditingRoute extends \Frootbox\Routing\AbstractRoute
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
        \DI\Container $container,
        \Frootbox\Http\Response $response,
        \Frootbox\Session $session,
        $alias,
        ?\Frootbox\Persistence\Page $page,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config
    ): void
    {
        if (!IS_EDITOR or $page === null) {
            return;
        }

        if (preg_match('#\/edit$#', $_SERVER['REQUEST_URI']) or preg_match('#\/edit\/#', $_SERVER['REQUEST_URI'])) {

            $viewFile = dirname(__DIR__, 2) . '/resources/private/views/configButton.html.twig';

            $source = $view->render($viewFile, [
                'alias' => $alias,
                'page' => $page,
            ]);

            $body = $response->getBody();


            $body = str_replace('</body>', $source . '</body>', $body);

            $view->set('SID', SID);

            foreach ($config->get('editables') as $editable) {

                // Init editable
                $class = $editable['editable'] . '\\Editable';
                $editable = new $class;

                if (!method_exists($editable, 'initEditing')) {
                    continue;
                }

                $body = $container->call([ $editable, 'initEditing' ], [
                    'html' => $body,
                ]);
            }

            $response->setBody($body);
        }
        else {

            $viewFile = dirname(__DIR__, 2) . '/resources/private/views/editButton.html.twig';

            $source = $view->render($viewFile, [
                'alias' => $alias,
                'page' => $page,
                'query' => $_SERVER['QUERY_STRING'] ?? null,
            ]);

            $body = $response->getBody();

            $body = str_replace('</body>', $source . '</body>', $body);

            $response->setBody($body);
        }
    }
}
