<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class ApiRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^api/v([0-9]{1,})/(.*?)$#i';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\Config\Config $configuration,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): void
    {
        try {

            $clientId = $configuration->get('Api.Auth.Basic.ClientId');
            $clientSecret = $configuration->get('Api.Auth.Basic.ClientSecret');

            if (empty($_SERVER['PHP_AUTH_USER'])) {
                throw new \Exception('Auth user missing.');
            }

            if (empty($_SERVER['PHP_AUTH_PW'])) {
                throw new \Exception('Auth password missing.');
            }

            if ($clientId != $_SERVER['PHP_AUTH_USER'] or $clientSecret != $_SERVER['PHP_AUTH_PW']) {
                throw new \Exception('Authentication failed: invalid credentials.');
            }

            $request = explode('?', ORIGINAL_REQUEST_URI)[0];
            preg_match('#^/api/v([0-9]{1,})/([a-z]+)/([a-z]+)/([a-z]+)/([a-z]+)/(.*?)$#i', $request, $match);

            $class = '\\Frootbox\\Ext\\' . $match[2] . '\\' . $match[3] . '\\Api\\' . $match[4] . '\\' . $match[5] . '';

            $controller = $container->get($class);
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $action = $match[6] . 'Using' . ucfirst(strtolower($method));

            $response = $container->call([ $controller, $action ]);

            header('Content-Type: application/json; charset=utf-8');
            die(json_encode($response->getPayload(), JSON_UNESCAPED_UNICODE));
        }
        catch (\Exception $exception) {

            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                'error' => $exception->getMessage(),
            ], JSON_UNESCAPED_UNICODE));
        }
    }
}
