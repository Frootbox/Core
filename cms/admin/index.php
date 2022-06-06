<?php 
/**
 * 
 */

declare(strict_types = 1);

namespace Frootbox;

try {

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE);

    setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
    date_default_timezone_set('Europe/Berlin');

    // mb_internal_encoding('UTF-8');
    // mb_http_output('UTF-8');
    
    ob_start();

    define('REQUEST', null);

    // Global autoloader
    require '../../lib/autoload.php';
    
    // Admin autoloader
    spl_autoload_register(function ( $class ) {
                
        $adminClass = substr($class, 15);
        
        $file = CORE_DIR . 'cms/admin/classes/' . str_replace('\\', '/', $adminClass) . '.php';
                        
        if (file_exists($file)) {
            require_once $file;
        }
    });


    // Init front
    require '../classes/Front.php';
    $container = Front::init();

    $session = $container->get(\Frootbox\Session::class);

    $config = $container->get(\Frootbox\Config\Config::class);

    define('DEVMODE', !empty($config->get('devmode')));

    // Load extensions autoloader
    if (!file_exists($autoloadConfig = $config->get('filesRootFolder') . 'cache/system/autoload.php')) {

        $extensions = $container->get(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions->writeAutoloader($config);
    }

    try {
        require $autoloadConfig;
    }
    catch (\Frootbox\Exceptions\ResourceInvalid $e) {

        $extensions = $container->get(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions->writeAutoloader($config);

        require $autoloadConfig;
    }

    define('IS_EDITOR', true);

    $configStatics = $container->get(\Frootbox\ConfigStatics::class);

    define('SIGNING_TOKEN', $configStatics->getSigningToken());

    if (file_exists($adminConfigFile = $config->get('filesRootFolder') . 'config/adminpreconfig.php')) {
        $adminConfig = require $adminConfigFile;
        $config->prepend($adminConfig);        
    }
    
    if (file_exists($adminConfigFile = $config->get('filesRootFolder') . 'config/general.php')) {
    
        $adminConfig = require $adminConfigFile;

        if (is_array($adminConfig)) {
            $config->append($adminConfig);
        }
    }

    if (!IS_SSL and !empty($config->get('general.host.forceSSL'))) {

        $url = 'https://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['REQUEST_URI'];
        header('Location: ' . $url);
        exit;
    }


    if (!empty($domain = $config->get('general.host.domain'))) {

        if ($domain != $_SERVER['HTTP_HOST']) {

            // Check custom routing for specific domain
            if (!empty($config->get('general.domains'))) {
                d("CHECK REDIRECT");
            }

            // Force visitor to default domain
            $redirect = '//' . $domain . $_SERVER['REQUEST_URI'];

            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $redirect);
            header("Connection: close");
            exit;
        }
    }




    define('IS_WEBP', !empty($config->get('thumbnails.webp')));

    define('GLOBAL_LANGUAGE', 'de-DE');
    define('DEFAULT_LANGUAGE', $config->get('i18n.defaults')[0] ?? $config->get('i18n.languages')[0] ?? 'de-DE');
    define('MULTI_LANGUAGE', !empty($config->get('i18n.multiAliasMode')));

    $translationFactory = $container->get(\Frootbox\TranslatorFactory::class);
    $translator = $translationFactory->get(GLOBAL_LANGUAGE);


    $dispatcher = $container->get(\Frootbox\Admin\Dispatcher::class);



    $route = $dispatcher->dispatch();

    // Check controller route
    if (!class_exists($route['controller'])) {
        throw new \Frootbox\Exceptions\RuntimeError('ControllerNotFound');
    }

    $controller = $container->get($route['controller']);

    if (!method_exists($controller, $route['action'])) {
        throw new \Frootbox\Exceptions\RuntimeError('Method does not exist: ' . get_class($controller) . '::' . $route['action'] . '()');
    }


    // Init view and set classes
    $view = $container->get(\Frootbox\Admin\View::class);
    $view->set('container', $container);
    $view->set('serverpath', SERVER_PATH);
    $view->set('view', $view);
    $view->set('configuration', $config);
    $view->set('get', $container->get(\Frootbox\Http\Get::class));

    $view->set('settings', [
        'serverpath' => SERVER_PATH,
        'serverpath_absolute' => SERVER_PATH_PROTOCOL,
        'basepath' => CORE_DIR,
        'default_language' => DEFAULT_LANGUAGE,
    ]);

    $front = $container->get(\Frootbox\Admin\Front::class);
    $view->set('front', $front);


    // Init user ession
    $session = $container->get(\Frootbox\Session::class);




    define('IS_LOGGED_IN', $session->isLoggedIn());

    if ($session->isLoggedIn()) {

        try {

            // Obtain logged in user
            $user = $session->getUser();

            // Set lastclick
            $user->setLastClick(date('Y-m-d H:i:s'));
            $user->save();

            $view->set('user', $user);
        }
        catch ( \PDOException $e ) {

            // Ignore potentially exception during updating ser
        }
        catch ( \Frootbox\Exceptions\NotFound $e ) {

            // Catch potentially previously logged in and now deleted user or legacy session during development
            $session->logout();
        }
    }

    $db = $container->get(\Frootbox\Db\Db::class);

    if ($session->isLoggedIn()) {
        $db->setVar('userId', $session->getUser()->getId());
    }

    // Perform main controller action
    $controller->setLastAction($route['action']);
    $response = $container->call([ $controller, $route['action']]);

    http_response_code(200);

    if (!is_object($response)) {
        throw new \Frootbox\Exceptions\RuntimeError('Unexpected Response Format: ' . gettype($response));
    }

    // Check response type
    if (!$response instanceof \Frootbox\Admin\Controller\Response) {
        throw new \Frootbox\Exceptions\RuntimeError('Unexpected Response Format: ' . get_class($response));
    }

    if ($response->getStatusGroup() == 200) {

        // Evaluate response
        if ($response->getType() == 'plain') {

            die($response->getBody());
        }
        elseif ($response->getType() == 'html') {

            $view = $controller->getView();

            $view->set('get', $container->get(\Frootbox\Http\Get::class));
            $view->set('container', $container);
            $view->set('controller', $controller);

            if (!empty($data = $response->getBodyData())) {
                foreach ($data as $key => $value) {
                    $view->set($key, $value);
                }
            }

            $controllerHtml = $controller->render($route['action']);

            $view->set('controllerHtml', $controllerHtml);

            if ($view->get('frame') !== false) {

                // Wrap view file
                $wrapperFile = $view->get('wrapper') ? $view->get('wrapper') : 'Index';
                $viewFile = CORE_DIR . 'cms/admin/resources/private/views/' . $wrapperFile . '.html.twig';

                $controllerHtml = trim($view->render(CORE_DIR . 'cms/admin/resources/private/views/' . $wrapperFile . '.html.twig', null));
            }

            die($controllerHtml);
        } elseif ($response->getType() == 'json') {

            header('Content-Type: application/json');
            die($response->getBody());
        }
        elseif ($response->getType() == 'attachment') {

            foreach ($response->getHeaders() as $header) {
                header($header);
            }

            die($response->getBody());
        }
    }
    elseif ($response->getStatusGroup() == 300) {

        header($response->getHttpStatusHeader());
        header('Location: ' . $response->getBody());
        exit;
    }
    else {

        d($response);
    }
}
catch ( \Frootbox\Exceptions\NotLoggedIn $e ) {

    $response = new \Frootbox\Http\Response;
    $response->withStatus(401)->setHeader('content-type', 'text/json')->setBody(json_encode([
            'error' => 'NotLoggedIn',
            'modal' => \Frootbox\Admin\Front::getUri('Session', 'ajaxModalLogin')
          ])
    );

    $response->flush();
}
catch ( \Exception $e ) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

    die($e->getMessage());
}
catch ( \Frootbox\Exceptions\BaseException $e ) {

    $response = new \Frootbox\Http\Response;
    $response->withStatus(500)->setBody(json_encode([ 'error' => $e->getMessage() ]));

    $message = !empty($e->getMessage()) ? $e->toString($translator) : get_class($e);

    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {

        header('Content-Type: application/json');
        die($message);
    }

    echo '<html>
        <head>
            <title>Fehler</title>
            
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
            
        </head>
        <body>
        
            <b>' . $message . '</b>';

            foreach ($e->getTrace() as $trace) {

                unset($trace['args']);

               //p($trace);
            }


        echo '</body>
    </html>';
}
catch ( \Twig\Error\RuntimeError $e ) {
    $e = $e->getPrevious();
    die($e->getMessage());
}