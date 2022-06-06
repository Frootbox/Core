<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-06-14
 */

namespace Frootbox\Admin\Controller\App;

use Frootbox\Admin\Controller\Response;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function index(
        \DI\Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Persistence\Repositories\Apps $apps
    ): Response
    {
        try {
            // Fetch admin app
            $app = $apps->fetchById($get->get('appId'));

            $this->view->set('get', $get);
            $this->view->set('app', $app);

            // Perform controller
            $action = ($get->get('a') ?? 'index') . 'Action';

            if (!method_exists($app, $action)) {
                throw new \Frootbox\Exceptions\RuntimeError('Controller ' . get_class($app) . ' misses action ' . $action);
            }

            $app->setAction($action);

            $response = $container->call([$app, $action]);
        }
        catch ( \Exception $e ) {

            d($e->getMessage());

            if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(500);
                die($e->getMessage());
            }

            if (!empty($app) and !empty($action) and $action != 'index') {

                header('Location: ' . $app->getUri('index', [ 'error' => $e->getMessage() ]));
                exit;
            }

            return $this->redirect('Dashboard', 'index', [ 'error' => $e->getMessage() ]);
        }

        if (!is_object($response)) {
            throw new \Frootbox\Exceptions\RuntimeError('Unexpected Response Format: ' . gettype($response));
        }
        
        // Check response type
        if (!$response instanceof Response) {
            throw new \Frootbox\Exceptions\RuntimeError('Unexpected Response Format: ' . get_class($response));
        }
                
        if ($response->getStatusGroup() == 200) {
        
            // Evaluate response
            if ($response->getType() == 'html') {

                $viewFile = $app->getPath() . 'resources/private/views/' . substr(ucfirst($action), 0, -6) . '.html.twig';

                if (!file_exists($viewFile)) {
                    $viewFile = $app->getPath() . 'resources/private/views/' . substr(ucfirst($action), 0, -6) . '.html';
                }

                $html = $this->view->render($viewFile, null, $response->getBodyData() ?? []);
                
                $this->view->set('appHtml', $html);
                
                return self::getResponse();
            }
            elseif ($response->getType() == 'json') {

                return $response;
            }
        }        
        
        return $response;
    }
}
