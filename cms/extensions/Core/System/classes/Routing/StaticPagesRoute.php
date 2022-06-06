<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Routing;

class StaticPagesRoute extends \Frootbox\Routing\AbstractRoute
{
    /**
     *
     */
    protected function getMatchingRegex(): string
    {
        return '#^static\/#';
    }

    /**
     *
     */
    public function performRouting(
        \DI\Container $container,
        \Frootbox\Config\Config $configuration,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): void
    {
        if (!defined('GLOBAL_LANGUAGE')) {

            define('GLOBAL_LANGUAGE', !empty($_SESSION['frontend']['language']) ? $_SESSION['frontend']['language'] : 'de-DE');
            define('DEFAULT_LANGUAGE', 'de-DE');
            define('MULTI_LANGUAGE', !empty($configuration->get('i18n.multiAliasMode')));
        }


        // Convert request to page class
        $class = explode('?', substr($this->request->getRequestTarget(), 7))[0];

        $segments = explode('/qs/', $class);
        $class = $segments[0];

        // Drop trailing slash
        if (substr($class, -1) == '/') {
            $class = substr($class, 0, -1);
        }

        // Inject additional query strings
        if (!empty($segments[1])) {

            $parts = explode('/', $segments[1]);

            if (count($parts) % 2 != 0) {

                $tail = array_pop($parts);

                $parts[] = 'querytail';
                $parts[] = $tail;
            }

            for ($i = 0; $i < count($parts); $i += 2) {

                $_GET[$parts[$i]] = $parts[($i + 1)];
            }

        }

        $class = explode('/', $class);

        $action = array_pop($class);
        $action = str_replace('.', '_', $action);

        $pageClass = array_pop($class);

        $className = '\\Frootbox\\' . implode('\\', $class) . '\\StaticPages\\' . $pageClass . '\\Page';

        $page = new $className;

        if (!method_exists($page, $action)) {
            throw new \Frootbox\Exceptions\RuntimeError("No Method " . $action . " in page " . $className);
        }

        // Perform page action
        $response = $container->call([ $page, $action ]);

        if (empty($response)) {
            $response = new \Frootbox\View\Response;
        }

        
        if ($response instanceof \Frootbox\View\ResponseRedirect) {

            if (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {

                header('Location: ' . $response->getTarget());
                exit;
            }
            else {
                die(json_encode([
                    'redirect' => $response->getTarget(),
                ]));
            }
        }

        if ($response instanceof \Frootbox\View\ResponseJson) {

            $data = $response->getData();

            if (!empty($data['html']) and $data['html'] == 'renderAction') {

                $viewFile = $page->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';

                $view->set('page', $page);
                $view->set('view', $view);

                $html = $view->render($viewFile, $response->getData());

                $data['html'] = htmlspecialchars_decode($html);
            }

            // Generate html fallback
            if (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {

                http_response_code(200);

                // Redirect instantly if there is no error or success message
                if (!empty($data['continue']) and empty($data['success'])) {
                    header('Location: ' . $data['continue']);
                    exit;
                }


                // Compose html
                $html = '<html>
                    <head>
                        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
                        <style>
                            .message {
                                margin: 50px 0;
                                padding: 10px;
                            }
                        </style>
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                    </head>
                    <body>
                        <div class="container">
                            <div class="row">
                                <div class="col-12">';

                if (!empty($data['success'])) {
                    $html .= '<div class="message border border-success text-center">' . $data['success'] . '</div>';
                }

                if (!empty($data['continue'])) {
                    $html .= '<p><a href="' . $data['continue'] . '">fortfahren</a></p>';
                }

                $html .= '</div>
                        </div>
                    </div>
                </body></html>';

                die($html);
            }

            http_response_code(200);
            header('Content-Type: application/json');

            die(json_encode($data, JSON_HEX_QUOT));
        }

        if (empty($response->getBody())) {

            // Get view file
            $viewFiles = [];
            $viewFiles[] = $page->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';
            $viewFiles[] = $page->getPath() . 'resources/private/views/' . ucfirst($action) . '.html';
            $viewFiles[] = $page->getPath() . 'resources/private/views/View.html';

            foreach ($viewFiles as $file) {

                if (file_exists($file)) {
                    break;
                }
            }

            $view->set('page', $page);
            $view->set('view', $view);
            $html = $view->render($file, $response->getData());

            // Inject custom css
            $extensions = $extensionsRepository->fetch([
                'where' => [
                    'isactive' => 1,
                ],
            ]);

            preg_match('#\\\\([a-z0-9]+)\\\\([a-z0-9]+)\\\\StaticPages\\\\([a-z0-9]+)\\\\Page$#i', get_class($page), $match);

            foreach ($extensions as $extension) {

                $stylepath = $extension->getExtensionController()->getPath() . 'resources/public/css/styles-variables.less';

                if (file_exists($stylepath)) {
                    $html = str_replace('</head>', '<link rel="stylesheet/less" type="text/css" href="FILE:' . $stylepath . '" />' . PHP_EOL . '</head>', $html);
                }

                $stylepath = $extension->getExtensionController()->getPath() . 'staticpages/' . $match[1] . '/' . $match[2] . '/' . $match[3] . '/resources/public/css/standards.less';

                if (file_exists($stylepath)) {
                    $html = str_replace('</head>', '<link rel="stylesheet/less" type="text/css" href="FILE:' . $stylepath . '" />' . PHP_EOL . '</head>', $html);
                }
            }

            // Inject css
            $stylepath = $page->getPath() . 'resources/public/css/standards.less';

            if (file_exists($stylepath)) {
                $html = str_replace('</head>', '<link rel="stylesheet/less" type="text/css" href="FILE:' . $stylepath . '" />' . PHP_EOL . '</head>', $html);
            }


            // Parse html
            $parser = new \Frootbox\View\HtmlParser($html, $container);
            $html = $container->call([ $parser, 'parse' ]);

            die($html);
        }
    }
}
