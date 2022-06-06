<?php
/**
 *
 */

namespace Frootbox\Routing;

class Router
{
    protected $routes = [ ];

    /**
     *
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     *
     */
    public function performRouting(
        \Frootbox\Http\ClientRequest $request,
        \DI\Container $container,
        \Frootbox\Session $session,
        $alias,
        $page
    ): void
    {
        foreach ($this->routes as $routeData) {

            $routeClass = $routeData['route'];

            if (!class_exists($routeClass)) {
                throw new \Frootbox\Exceptions\ClassMissing(null, [ $routeClass ]);
            }

            $route = new $routeClass($request);

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
