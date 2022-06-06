<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2021-03-28
 */

namespace Frootbox\Admin\Controller\StaticController;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function index(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {
        // Obtain controller
        $segments = explode('/', $get->get('segment'));
        $className = '\\Frootbox\\Ext\\' . $segments[0] . '\\' . $segments[1] . '\\Admin\\StaticControllers\\' . $segments[2] . '\\Controller';

        $controller = new $className;

        // Call controller action
        $action = $get->get('a');

        if (!method_exists($controller, $action)) {
            throw new \Frootbox\Exceptions\RuntimeError("ActionMissing");
        }

        $response = $container->call([ $controller, $action ]);

        // Return json
        if ($response->getType() == 'json') {
            return $response;
        }

        // Render output
        $viewFile = $controller->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';

        $html = $view->render($viewFile, null, $response->getBodyData());

        return new \Frootbox\Admin\Controller\Response('plain', 200, $html);
    }
}
