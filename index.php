<?php 
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com> 
 * @date 2018-06-15
 */

namespace Frootbox;

try {

    // phpinfo();exit;

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    // mb_internal_encoding('UTF-8');
    // mb_http_output('UTF-8');
        
    setlocale (LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
    date_default_timezone_set('Europe/Berlin');

    require 'lib/autoload.php';
    require 'cms/classes/Front.php';

    $container = Front::init();

    // Generate client request
    $requestTarget = str_replace(dirname($_SERVER['PHP_SELF']) . '/', '', $_SERVER['REQUEST_URI']);

    $requestTarget = explode('?', $requestTarget)[0];

    if (!empty($requestTarget) and $requestTarget[0] == '/') {
        $requestTarget = ltrim($requestTarget, '/');
    }

    if (preg_match('#\/{2,}#', $_SERVER['REQUEST_URI'])) {

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . rtrim(SERVER_PATH_PROTOCOL . $requestTarget,'/'));
        header("Connection: close");
        exit;
    }

    $request = $container->get(\Frootbox\Http\ClientRequest::class);
    $request->setRequestTarget($requestTarget)->setQueryParameters($_GET);

    $configuration = $container->get(\Frootbox\Config\Config::class);

    if (!empty($domain = $configuration->get('general.host.domain'))) {

        if ($domain != $_SERVER['HTTP_HOST']) {

            // Check custom routing for specific domain
            if (!empty($configuration->get('general.domains'))) {
                d("CHECK REDIRECT");
            }

            // Force visitor to default domain
            if (!IS_SSL and $configuration->get('general.host.forceSSL')) {
                $redirect = 'https://' . $domain . '/' . $requestTarget;
            }
            else {
                $redirect = '//' . $domain . '/' . $requestTarget;
            }

            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $redirect);
            header("Connection: close");
            exit;
        }
    }

    if (!IS_SSL and $configuration->get('general.host.forceSSL')) {

        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $requestTarget;

        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $redirect);
        header("Connection: close");
        exit;
    }

    // Instantiate view
    $view = $container->get(\Frootbox\View\Engines\Interfaces\Engine::class);

    $view->set('settings', [
        'serverpath' => SERVER_PATH,
        'serverpath_absolute' => SERVER_PATH_PROTOCOL,
        'basepath' => CORE_DIR
    ]);


    // Load extensions autoloader
    if (!file_exists($autoloadConfig = $configuration->get('filesRootFolder') . 'cache/system/autoload.php')) {

        $extensions = $container->get(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions->writeAutoloader($configuration);
    }


    try {
        require $autoloadConfig;
    }
    catch (\Frootbox\Exceptions\ResourceInvalid $e) {

        $extensions = $container->get(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions->writeAutoloader($configuration);

        require $autoloadConfig;
    }

    // Load admin config variables
    if (file_exists($adminConfigFile = $configuration->get('filesRootFolder') . 'config/general.php')) {

        $adminConfig = require $adminConfigFile;

        $configuration->append($adminConfig);
    }

    $configStatics = $container->get(\Frootbox\ConfigStatics::class);

    $session = $container->get(\Frootbox\Session::class);

    define('SIGNING_TOKEN', $configStatics->getSigningToken());
    define('IS_LOGGED_IN', $session->isLoggedIn());
    define('REQUEST', $request->getRequestTarget());
    define('IS_EDITOR', IS_LOGGED_IN and !empty($_SESSION['user']['type']) and $_SESSION['user']['type'] != 'User');

    define('IS_WEBP', !empty($configuration->get('thumbnails.webp')));


    // Initialize routing
    $routes = [
        [
            'route' => \Frootbox\Ext\Core\System\Routing\StaticPagesRoute::class,
        ],
    ];

    // Initialize custom routing
    if (!empty($customRoutes = $configuration->get('routes'))) {
        $routes = array_merge($customRoutes->getData(), $routes);
    }

    // Setup router
    $router = $container->make(\Frootbox\Routing\Router::class, [
        'routes' => $routes
    ]);

    $container->call([ $router, 'performRouting' ], [
        'request' => $request,
        'alias' => null,
        'page' => null,
    ]);

    // TODO: alle router zurückbauen
    $requestX = $request;
    $request = $request->getRequestTarget();


    if (preg_match('/[A-Z]/', $request)){

        header("HTTP/1.1 301 Moved Permanently");
        header('Location: ' . SERVER_PATH_PROTOCOL . strtolower($request) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
        exit;
    }

    if (!empty($request) and substr($request, 0, 7) == 'widget/') {

        $segs = explode('/', $request);
        array_shift($segs);

        $widgetId = array_shift($segs);
        $action = array_shift($segs);


        // Extract get data from url
        while ($key = current($segs)) {

            next($segs);
            $_GET[$key] = current($segs);
            next($segs);
        }


        // Fetch widget
        $widgets = $container->get(\Frootbox\Persistence\Content\Repositories\Widgets::class);
        $widget = $widgets->fetchById($widgetId);

        $container->call([ $widget, $action . 'Action' ]);
    }

    /**
     * Perform ajax action
     */
    if (!empty($request) and substr($request, 0, 5) == 'ajax/') {

        define('GLOBAL_LANGUAGE', (!empty($_SESSION['frontend']['language']) ? $_SESSION['frontend']['language'] : 'de-DE'));
        define('DEFAULT_LANGUAGE', 'de-DE');
        define('MULTI_LANGUAGE', !empty($configuration->get('i18n.multiAliasMode')));

        // Fetch plugin
        $plugins = $container->get(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $plugin = $plugins->fetchById($_GET['pluginId']);

        $plugin->setAttributes($_GET);

        $action = 'ajax' . ucfirst($_GET['action']);

        $view->addGlobal('view', $view);

        // Call ajax method on plugin
        $response = $container->call([ $plugin, $action . 'Action' ]);


        // Convert empty responses to default response class to retain backward compatibility
        if (empty($response)) {
            $response = new \Frootbox\View\Response;
        }

        if (($response instanceof \Frootbox\View\ResponseJson or $response instanceof \Frootbox\View\ResponseRedirect) and strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
            $response = \Frootbox\View\Response::createFromJsonResponse($response);
        }

        if ($response instanceof \Frootbox\View\ResponseRedirect) {
            $response = new \Frootbox\View\ResponseJson([
                'redirect' => $response->getTarget(),
            ]);
        }

        if ($response instanceof \Frootbox\View\ResponseView) {

            foreach ($response->getData() as $var => $value) {
                $view->addGlobal($var, $value);
            }

            $html = $container->call([ $plugin, 'renderHtml'], [
                'action' => $action
            ]);

            // Parse html
            $parser = new \Frootbox\View\HtmlParser($html, $container);
            $html = $container->call([ $parser, 'parse' ]);

            $response->setBody($html);
        }

        foreach ($response->getHeaders() as $header) {
            header($header);
        }

        http_response_code(200);

        die($response->getBody());
    }

    if (empty($request) or $request == '/') {

        // Fetch root alias
        $aliases = $container->get(\Frootbox\Persistence\Repositories\Aliases::class);
        $alias = $aliases->fetchOne([
            'where' => [
                'alias' => '',
            ],
        ]);

        // Fetch current page
        $pages = $container->get(\Frootbox\Persistence\Repositories\Pages::class);
        $result = $pages->fetch([
            'where' => [
                'parentId' => 0,
            ],
            'limit' => 1
        ]);

        if ($result->getCount() == 0) {
            throw new \Frootbox\Exceptions\ResourceMissing('No root page is configured.');
        }

        $page = $result->current();
    }
    else {

        // Process request
        $aliases = $container->get(\Frootbox\Persistence\Repositories\Aliases::class);

        $alias = $aliases->fetchOne([
            'where' => [
                'alias' => trim($request, '/'),
            ],
        ]);

        if ($alias === null and !empty($_SERVER['QUERY_STRING'])) {

            $alias = $aliases->fetchOne([
                'where' => [
                    'alias' => $request . '?' . $_SERVER['QUERY_STRING'],
                ],
            ]);
        }

        if ($alias and $alias->getStatus() == 200 and empty($alias->getVisibility())) {
            // $alias = null;
        }

        if (!$alias) {

            if (preg_match('#cache\/images#', $request)) {

                $path = str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $_SERVER['REQUEST_URI']) . '.xdata.json';

                $json = file_get_contents($path);

                $request = json_decode($json, true);

                $query = http_build_query($request);

                $location = SERVER_PATH_PROTOCOL . 'static/Ext/Core/Images/Thumbnail/render?' . $query;

                header("HTTP/1.1 307 Temporary Redirect");
                header("Location: " . $location);
                exit;
            }

            // Initialize error catch routing
            $routes = [
                [
                    'route' => \Frootbox\Ext\Core\System\Routing\CustomRewritesRoute::class
                ],
                [
                    'route' => \Frootbox\Ext\Core\System\Routing\SitemapRoute::class
                ],
                [
                    'route' => \Frootbox\Ext\Core\System\Routing\RobotsTxtRoute::class
                ],
                [
                    'route' => \Frootbox\Ext\Core\System\Routing\FaviconRoute::class
                ],
                [
                    'route' => \Frootbox\Ext\Core\System\Routing\ApiRoute::class
                ]
            ];

            if (!empty($configuration->get('failroutes'))) {
                array_push($routes, ...$configuration->get('failroutes')->getData());
            }

            // Setup router
            $router = $container->make(\Frootbox\Routing\Router::class, [
                'routes' => $routes
            ]);

            $container->call([ $router, 'performRouting' ], [
                'request' => $requestX,
                'alias' => null,
                'page' => null,
            ]);

            if (!empty($_GET['fromCache'])) {

                http_response_code(404);
                die("Cache miss :-/");
            }

            // If passed custom routing check for error page
            $pagesRepository = $container->get(\Frootbox\Persistence\Repositories\Pages::class);
            $page = $pagesRepository->fetchOne([
                'where' => [
                    'type' => 'Error404',
                ],
            ]);

            if (!$page) {
                if (!empty($_SESSION['user']['id'])) {
                    throw new \Frootbox\Exceptions\ResourceMissing('Page', [ $request ]);
                }

                http_response_code(404);

                die('<html><head><meta http-equiv="refresh" content="0;' . SERVER_PATH_PROTOCOL . '" /><title>Die Seite wurde nicht gefunden.</title></head><body><p>Die Seite wurde nicht gefunden.</p></body></html>');
            }
        }

        if (empty($page)) {

            $status = $alias->getStatus();

            if ($status == 301) {

                if (preg_match('#^fbx:\/\/page:([0-9]{1,})$#', $alias->getConfig('target'), $match)) {

                    // If passed custom routing check for error page
                    $pagesRepository = $container->get(\Frootbox\Persistence\Repositories\Pages::class);
                    $page = $pagesRepository->fetchById($match[1]);

                    define('MULTI_LANGUAGE', !empty($configuration->get('i18n.multiAliasMode')));

                    $target = $page->getUri();
                }
                else {
                    $target = $alias->getConfig('target');
                }

                $target = SERVER_PATH_PROTOCOL . trim($target, '/');

                header('Location: ' . $target, true, 301);
                exit;
            }

            // Fetch current page
            $pages = $container->get(\Frootbox\Persistence\Repositories\Pages::class);
            $page = $pages->fetchById($alias->getPageId());
        }
    }

    // Set language
    $language = ($alias and !empty($alias->getLanguage())) ? $alias->getLanguage() : $page->getLanguage();

    $language = !empty($_GET['forceLanguage']) ? $_GET['forceLanguage'] : $language;

    define('GLOBAL_LANGUAGE', $language);
    define('MULTI_LANGUAGE', !empty($configuration->get('i18n.multiAliasMode')));
    define('DEFAULT_LANGUAGE', $configuration->get('i18n.defaults')[0] ?? $configuration->get('i18n.languages')[0] ?? 'de-DE');

    $_SESSION['frontend']['language'] = GLOBAL_LANGUAGE;

    // Perform security check
    if ($page->getVisibility() == 'Moderated' and !IS_EDITOR) {
        header('HTTP/1.1 401 Unauthorized', true);
        header("X-Robots-Tag: noindex, nofollow", true);

        die("Der Zugriff auf diese Seite wurde beschränkt.");
    }
    else if ($page->hasRestrictedAccess() and !$page->isAccessGranted()) {

        if (!empty($page->getConfig('security.internal'))) {

            $contentElements = $container->get(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
            $login = $contentElements->fetchOne([
                'where' => [
                    'className' => \Frootbox\Ext\Core\System\Plugins\Login\Plugin::class,
                ]
            ]);

            if (!$login) {
                die("NO LOGIN PAGE FOUND");
            }

            header('Location: ' . $login->getActionUri('index', [ 'referer' => urlencode($_SERVER['REQUEST_URI']) ]), true);
            exit;
        }

        header('Location: ' . SERVER_PATH . 'static/Ext/Core/System/Auth/password?pageId=' . $page->getId() . '&referer=' . urlencode($_SERVER['REQUEST_URI']), true);
        exit;
    }




    $view->set('settings', array_merge($view->get('settings'), [
        'language' => GLOBAL_LANGUAGE,
        'isEditor' => IS_EDITOR,
        'isLoggedIn' => IS_LOGGED_IN,
    ]));

    $view->addGlobal('globalLanguage', GLOBAL_LANGUAGE);
    $view->addGlobal('config', $configuration);
    $view->addGlobal('page', $page);
    $view->addGlobal('alias', $alias ?? null);
    $view->addGlobal('view', $view);

    if (!empty($page->getConfig('variables'))) {
        $view->addGlobal('pageVariables', $page->getConfig('variables'));
    }

    if ($page->getPageType() == 'Redirect') {

        $target = $page->getConfig('redirect.target');

        if (preg_match('#^fbx\:\/\/page\:([0-9]{1,})$#', $target, $match)) {

            try {
                $targetPage = $pages->fetchById($match[1]);
            }
            catch ( \Frootbox\Exceptions\NotFound $exception ) {
                die("Invalid redirect target.");
            }


            header('Location: ' . $targetPage->getUri());
            exit;
        }
        else {

            header('Location: ' . $page->getConfig('redirect.target'));
            exit;

        }
    }
    elseif ($page->getPageType() == 'Frame') {

        $viewFile = CORE_DIR . 'cms/resources/private/views/Frame.html.twig';
        die($view->render($viewFile));
    }

    if (!empty($configuration->get('pageRootFolder'))) {
        $view->addPath($configuration->get('pageRootFolder'));
    }

    // Replace translations
    $translationFactory = $container->get(\Frootbox\TranslatorFactory::class);
    $translator = $translationFactory->get($page->getLanguage());

    // Get sockets config from cache
    $key = md5($_SERVER['REQUEST_URI']);
    $cacheFile = FILES_DIR . 'cache/system/sockets/' . $key . '.php';

    if (!file_exists($cacheFile)) {

        // Render page layout html
        $layout = $configuration->get('layoutRootFolder') . $page->getLayout();

        $html = $view->render($layout);

        // Render sockets
        preg_match_all('#<div(.*?)data-socket="(.*?)"(.*?)></div>#i', $html, $xmatches);

        $matches = [];
        $orderId = 100000;

        foreach ($xmatches[0] as $index => $line) {

            if (preg_match('#data-order="([0-9]+)"#', $line, $nmatch)) {
                $xOrderId = $nmatch[1];
            } else {
                $xOrderId = ++$orderId;
            }

            $matches[0][$xOrderId] = $line;
            $matches[1][$xOrderId] = $xmatches[1][$index];
            $matches[2][$xOrderId] = $xmatches[2][$index];
            $matches[3][$xOrderId] = $xmatches[3][$index];
        }


        if (!empty($matches)) {
            ksort($matches[0]);
        }

        if (!file_exists($cacheDir = dirname($cacheFile))) {
            mkdir($cacheDir, 0700, true);
        }

        file_put_contents($cacheFile, '<?php return ' . var_export($matches, true) . ";\n");
    }

    $matches = require $cacheFile;

    $contentElements = $container->get(\Frootbox\Persistence\Repositories\ContentElements::class);
    $get = $container->get(\Frootbox\Http\Get::class);

    $view->set('get', $get);

    $htmlSnippets = [ ];

    $layout = $configuration->get('layoutRootFolder') . $page->getLayout();


    if (!empty($matches)) {
        foreach ($matches[0] as $index => $tagline) {

            $socket = $matches[2][$index];

            $sql = 'SELECT
                e.*
            FROM
                pages p,
                content_elements e
            WHERE
                e.socket = "' . $socket . '" AND            
                e.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND    
                
                (
                    ( e.pageId = ' . $page->getId() . ' AND e.pageId = p.id ) OR
                    ( e.pageId = p.id AND e.inheritance = "Inherited" AND p.lft < ' . $page->getLft() . ' AND p.rgt > ' . $page->getRgt() . ')    
                )
            ORDER BY orderId DESC';

            $result = $contentElements->fetchByQuery($sql);

            $contentReplacements = [];

            $loop = 1;

            $mainContent = preg_match('#data\-main#', $tagline);

            foreach ($result as $contentElement) {

                if (!empty($contentElement->getConfig('skipLanguages.' . GLOBAL_LANGUAGE))) {
                    continue;
                }

                if ($mainContent and $loop == 1) {
                    $contentElement->setFirst();
                }


                switch ($contentElement->getType()) {

                    case 'Plugin':

                        // Extract plugins payload
                        if (isset($alias)) {
                            $payload =  $container->call([$alias, 'getPayloadData'], ['contentElement' => $contentElement]);
                        }
                        else {

                            $injectedGetData = $get->get('p') ?? [ ];

                            if (!empty($injectedGetData[$contentElement->getId()])) {
                                $payload = $injectedGetData[$contentElement->getId()];
                            }
                            else {
                                $payload = [];
                            }
                        }

                        if (!empty($payload)) {
                            $contentElement->setAttributes($payload);
                        }


                        // Generate action
                        $action = $payload['action'] ?? 'index';
                        $method = $action . 'Action';


                        $contentElement->setPage($page);

                        // Call plugin action
                        if (method_exists($contentElement, $method)) {

                            // Call action
                            $result = $container->call([$contentElement, $method]);

                            if (!empty($result)) {

                                if ($result instanceof \Frootbox\View\ResponseRedirect) {
                                    http_response_code(200);
                                    header('Location: ' . $result->getTarget());
                                    exit;
                                }
                                else {

                                    foreach ($result->getData() as $key => $value) {
                                        $view->set($key, $value);
                                    }
                                }
                            }
                        }


                        // Render html
                        $htmlFragment = $container->call([$contentElement, 'renderHtml'], [
                            'action' => $action
                        ]);
                        break;


                    case 'Text':
                    case 'Grid':

                        $htmlFragment = $container->call([$contentElement, 'renderHtml'], [
                            'action' => 'index',
                            'order' => $loop
                        ]);

                        break;
                }

                ++$loop;

                $parameters = [];

                // Parse parameters
                $htmlFragment = preg_replace_callback('#<param name="(.*?)" value="(.*?)" />#',
                    function ($match) use (&$parameters) {

                        $parameters[$match[1]] = $match[2];

                    }, $htmlFragment);

                $contentReplacements[] = [
                    'parameters' => $parameters,
                    'html' => $htmlFragment
                ];
            }

            foreach ($contentReplacements as $index => $replacement) {

                foreach ($replacement['parameters'] as $parameter => $value) {

                    switch ($parameter) {

                        case 'cloak':
                            $contentElement->setFirst();

                            $contentReplacements = [
                                $replacement
                            ];
                            break;

                        case 'layout':

                            $layout = dirname($layout) . '/' . $value . '.html';

                            if (!file_exists($layout)) {
                                $layout .= '.twig';
                            }
                            break;

                        case 'pageMaster':

                            $layout = null;
                            break 4;



                    }
                }
            }

            $output = (string)null;

            foreach ($contentReplacements as $replacement) {

                $output .= "\n\n\n" . trim($replacement['html']);
            }

            // $socketHtml = str_replace('></div>', ">\n" . $output . "\n</div>", $tagline);

            $socketHtml = str_replace('</div>', PHP_EOL . $output . PHP_EOL . '</div>', $tagline);

            $htmlSnippets[] = [
                'tagline' => $tagline,
                'html' => $socketHtml
            ];

            // $html = str_replace($tagline, $socketHtml, $html);;
        }
    }

    if ($layout !== null) {
        $html = $view->render($layout);
    }
    elseif ($layout === null) {
        $html = $replacement['html'];
    }

    foreach ($htmlSnippets as $snippet) {
        $html = str_replace($snippet['tagline'], $snippet['html'], $html);
    }

    // Generate response
    $response = $container->get(\Frootbox\Http\Response::class);
    $response->setBody($html);

    // Parse html
    $parser = new \Frootbox\View\HtmlParser($html, $container);
    $html = $container->call([ $parser, 'parse' ]);

    if (!preg_match('#<h1.*?>#', $html)) {

        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('header.h2.main-title')->first()->each(function($element) {

            $element->removeClass('h2');
            $element->addClass('h1');

            $html = $element->getInnerHtml();
            $html = str_replace('<h2', '<h1', $html);
            $html = str_replace('</h2>', '</h1>', $html);
            $element->setInnerHtml($html);
        });

        $html = $crawler->saveHTML();
    }



    $response->setBody($html);;


    // Initialize custom post routing
    if (!empty($routes = $configuration->get('postroutes'))) {

        // Setup router
        $router = $container->make(\Frootbox\Routing\Router::class, [
            'routes' => $routes->getData()
        ]);

        $container->call([ $router, 'performRouting' ], [
            'request' => $requestX,
            'alias' => $alias ?? null,
            'page' => $page
        ]);
    }

    $html = $response->getBody();


    if (!empty($injectPublicsPlain = $configuration->get('injectPublicsPlain'))) {

        $script = (string) null;

        foreach ($injectPublicsPlain->getData() as $scriptFile) {

            if (!is_array($scriptFile)) {
                $script .= file_get_contents($scriptFile) . PHP_EOL . PHP_EOL;
            }
            else {

                d($scriptFile);

            }


        }

        $html = str_replace('</body>', '<script>' . $script . '</script></body>', $html);
    }

    /*
    $parser = new \Frootbox\View\HtmlParser($html, $container);
    $container->call([ $parser, 'rewriteTags' ]);
    $html = $parser->getHtml();
    */

    /* Eventuell überflüssig?
    if (preg_match_all('#\"fbx\:\/\/page\:([0-9]{1,})\"#', $html, $matches)) {

        $pages = $container->get(\Frootbox\Persistence\Repositories\Pages::class);

        foreach ($matches[0] as $index => $tagline) {

            $page = $pages->fetchById($matches[1][$index]);

            $html = str_replace($tagline, '"' . $page->getUri() . '"', $html);
        }
    }
    */





    if (!$page->isIndexable()) {
        $html = str_replace('</head>', '<meta name="robots" content="noindex">' . PHP_EOL . '</head>', $html);
    }

    if ($page->getType() == 'Error404') {
        http_response_code(404);
    }

    // Cleanups
    $html = str_replace('&#150;', '&ndash;', $html);
    $html = preg_replace('#</source>#', '<!-- removed closing source  tag -->', $html);


    echo $html;
}
catch (\Frootbox\Exceptions\NotFound $exception) {

    if (!isset($requestX)) {
        die($exception->getMessage());
    }

    // Initialize error catch routing
    $routes = [
        [
            'route' => \Frootbox\Ext\Core\System\Routing\CustomRewritesRoute::class
        ],
        [
            'route' => \Frootbox\Ext\Core\System\Routing\SitemapRoute::class
        ],
        [
            'route' => \Frootbox\Ext\Core\System\Routing\RobotsTxtRoute::class
        ],
        [
            'route' => \Frootbox\Ext\Core\System\Routing\FaviconRoute::class
        ]
    ];

    if (!empty($configuration->get('failroutes'))) {
        array_push($routes, ...$configuration->get('failroutes')->getData());
    }

    // Setup router
    $router = $container->make(\Frootbox\Routing\Router::class, [
        'routes' => $routes
    ]);

    $container->call([ $router, 'performRouting' ], [
        'request' => $requestX,
        'alias' => null,
        'page' => null,
    ]);

    if (!empty($_GET['fromCache'])) {

        http_response_code(404);
        die("Cache miss :-/");
    }

    // If passed custom routing check for error page
    $pagesRepository = $container->get(\Frootbox\Persistence\Repositories\Pages::class);
    $page = $pagesRepository->fetchOne([
        'where' => [
            'type' => 'Error404',
        ],
    ]);

    if (!$page) {
        if (!empty($_SESSION['user']['id'])) {
            throw new \Frootbox\Exceptions\ResourceMissing('Page', [ $request ]);
        }

        http_response_code(404);

        die('<html><head><meta http-equiv="refresh" content="0;' . SERVER_PATH_PROTOCOL . '" /><title>Die Seite wurde nicht gefunden.</title></head><body><p>Die Seite wurde nicht gefunden.</p></body></html>');
    }

    d("404");
}
catch (Exceptions\Interfaces\Exception $exception) {

    if (!empty($container)) {

        $translatorFactory = $container->get(\Frootbox\TranslatorFactory::class);
        $translator = $translatorFactory->get('de-DE');

        $errorMessage = $translator->translate($exception->toString(), $exception->getProperties());
    }
    else {
        $errorMessage = $exception->toString();
    }

    http_response_code(500);

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {

        header('Content-Type: application/json');

        die(json_encode([
            'error' => $errorMessage
        ]));
    }

    echo '<html><head>
        <style>
            body { padding: 40px 0 0 0; background: #FFF; text-align: center; font-family: Arial; color: #CCC; }
        </style>
    </head>
    <body>
        ' . $errorMessage . '    
    </body>';

}
catch ( \PDOException $exception ) {

    // echo $exception->getMessage();d($exception->getTraceAsString());

    http_response_code(500);

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {

        header('Content-Type: application/json');

        die(json_encode([
            'error' => $exception->getMessage(),
        ]));
    }

    echo '<html><head>
        <style>
            body { padding: 40px 0 0 0; background: #FFF; text-align: center; font-family: Arial; color: #CCC; }
        </style>
    </head>
    <body>
        ' . $exception->getMessage() . '    
    </body>';

}
catch ( \Exception $exception ) {

    // echo $exception->getMessage();d($exception->getTraceAsString());

    http_response_code(500);

    if (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {

        header('Content-Type: application/json');

        die(json_encode([
            'error' => $exception->getMessage()
        ]));
    }


    echo '<html><head>
        <style>
            body { padding: 40px 0 0 0; background: #FFF; text-align: center; font-family: Arial; color: #CCC; }
        </style>
    </head>
    <body>';

    echo $exception->getMessage();


    echo "<br />" . $exception->getFile() . '@' . $exception->getLine();

    echo '</body>';
}
