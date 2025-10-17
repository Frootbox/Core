<?php
/**
 * @noinspection SqlNoDataSourceInspection
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
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        $multiAliasMode = !empty($configuration->get('i18n.multiAliasMode'));
        $defaultLanguage = $configuration->get('i18n.defaults')[0] ?? $configuration->get('i18n.languages')[0] ?? 'de-DE';


        $target = $this->request->getRequestTarget();

        if (!$multiAliasMode) {

            // Fetch aliases
            $result = $aliasesRepository->fetch([
                'where' => [
                    'status' => 200,
                    'visibility' => 2,
                ],
            ]);

        }
        else {

            $sql = 'SELECT * FROM aliases WHERE status = 200 AND language = :Language';
            $result = $aliasesRepository->fetchByQuery($sql, [
                'Language' => $defaultLanguage,
            ]);

            $aliases = [];

            foreach ($result as $alias) {

                $nalias = [
                    'location' => SERVER_PATH_PROTOCOL . $alias->getAlias(),
                    'updated' => $alias->getUpdated(),
                    'links' => [],
                ];

                $xresult = $aliasesRepository->fetch([
                    'where' => [
                        'status' => 200,
                        'uid' => $alias->getUid(),
                    ],
                ]);

                foreach ($xresult as $xalias) {
                    $nalias['links'][] = [
                        'language' => substr($xalias->getLanguage(), 0, 2),
                        'href' => SERVER_PATH_PROTOCOL . $xalias->getAlias(),
                    ];
                }

                $aliases[] = $nalias;
            }
        }


        $view->set('aliases', $aliases);

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
