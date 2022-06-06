<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class CustomRewritesRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#(.*?)#i';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\Config\Config $config
    ): void
    {
        if (empty($config->get('customRedirects'))) {
            return;
        }

        $redirects = json_decode(file_get_contents($config->get('customRedirects')), true);
        $target = rtrim($this->getRequest()->getRequestTarget(), '/');

        foreach ($redirects['redirections'] as $redirection) {

            if ($target == rtrim($redirection['request'], '/')) {
                header('Location: ' . SERVER_PATH_PROTOCOL . rtrim($redirection['url'], '/'));
                exit;
            }
        }
    }
}
