<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 */

namespace Frootbox\Routing;

class Router
{
    protected $routes = [ ];

    /**
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param \Frootbox\Http\ClientRequest $request
     * @param \DI\Container $container
     * @param \Frootbox\Session $session
     * @param $alias
     * @param $page
     * @return void
     * @throws \Frootbox\Exceptions\ClassMissing
     */
    public function performRouting(
        \Frootbox\Http\ClientRequest $request,
        \DI\Container $container,
        \Frootbox\Session $session,
        \Frootbox\Config\Config $config,
        $alias,
        $page
    ): void
    {
        foreach ($this->routes as $routeData) {

            $routeClass = $routeData['route'];

            if (!class_exists($routeClass)) {
                throw new \Frootbox\Exceptions\ClassMissing(null, [ $routeClass ]);
            }

            // Build route
            $route = new $routeClass($request, $config);

            if ($route->match()) {
                $container->call([ $route, 'performRouting' ], [
                    'request' => $request,
                    'alias' => $alias,
                    'page' => $page
                ]);
            }
        }
    }
}
