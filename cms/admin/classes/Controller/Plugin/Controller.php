<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2019-07-11
 */

namespace Frootbox\Admin\Controller\Plugin;

use Frootbox\Admin\Controller\Response;
use Frootbox\Config\Config;
use Frootbox\Http\Interfaces\ResponseInterface;
use Frootbox\View\ResponseRedirect;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxModalContainerCompose(
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        // Fetch extensions
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        return new Response('html', 200, [
            'extensions' => $extensions,
        ]);
    }

    /**
     *
     */
    public function ajaxPanelAction(
        \DI\Container $container,
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Persistence\Repositories\Pages $pages,
        \Frootbox\Config\Config $config
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        $plugin->importConfig($config);

        // Fetch recent page
        $page = $pages->fetchById($get->get('pageId') ?? $plugin->getPageId());

        $plugin->setPage($page);

        $view->set('plugin', $plugin);
        $view->set('page', $page);

        // Build admin controller
        $adminClass = substr(get_class($plugin), 0, -6) . 'Admin\\' . ucfirst($get->get('controller')) . '\\Controller';

        $pluginAdminController = new $adminClass($plugin);

        // Render plugin action
        if (!is_callable([ $pluginAdminController, $get->get('action') . 'Action' ])) {
            throw new \Frootbox\Exceptions\RuntimeError('Action ' . get_class($pluginAdminController) . '::' . $get->get('action') . 'Action() is not callable.');
        }

        $view->set('controller', $pluginAdminController);

        $response = $container->call([ $pluginAdminController, $get->get('action') . 'Action' ]);

        if (!$response instanceof Response) {
            throw new \Frootbox\Exceptions\RuntimeError('Unexpected response format.');
        }

        if ($response->getType() == 'json') {
            return $response;
        }

        if (!empty($payload = $response->getBodyData())) {

            foreach ($payload as $key => $value) {
                $view->set($key, $value);
            }
        }

        // Render plugins admin html context
        $pluginHtml = $container->call([ $pluginAdminController, 'render' ], [
            'action' => $get->get('action'),
        ]);

        if ($response->getType() == 'plain') {
            http_response_code(200);
            die($pluginHtml);
        }

        $view->set('pluginHtml', $pluginHtml);

        return self::getResponse('json', 200, [
            'anchor' => 'plugin:' . $get->get('pageId') . ':' . $plugin->getId() . ':Index:index',
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }


    /**
     * 
     */
    public function ajaxPanelConfig(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Config\Config $config,
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        // Obtain available sockets
        $sockets = $plugin->getPage()->getSockets($config);

        // Obtain available pages
        $pages = $pagesRepository->fetch();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render(null, [
                    'plugin' => $plugin,
                    'sockets' => $sockets,
                    'pages' => $pages,
                ]),
            ],
        ]);
    }


    /**
     *
     */
    public function ajaxPanelConfigGrid (
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Config\Config $config
    ) {
        // Fetch element
        $element = $contentElements->fetchById($get->get('gridId'));
        $view->set('element', $element);


        return self::response('json', 200, [
            'replace' => [
                'selector' => '#panelReceiver',
                'html' => self::render()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSwitchVisibility(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('elementId'));

        $plugin->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-element="' . $plugin->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2'
            ],
            'addClass' => [
                'selector' => '.visibility[data-element="' . $plugin->getId() . '"]',
                'className' => $plugin->getVisibilityString()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxContainerCreate(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): Response
    {
        // Get root page
        $page = $pagesRepository->fetchOne([
            'where' => [
                'parentId' => 0,
            ],
        ]);

        // Build content element
        $contentClass = $post->get('plugin');

        $containerElement = new $contentClass([
            'pageId' => $page->getId(),
            'socket' => 'ContainerPlugin',
            'type' => 'Plugin',
            'orderId' => 1,
            'visibility' => 1,
        ]);


        // Look for default plugin title
        if (file_exists($langfile = $containerElement->getPath() . 'resources/private/language/de-DE.php')) {

            $data = require $langfile;

            if (!empty($data['Plugin.TitleInitial'])) {
                $title = $data['Plugin.TitleInitial'];
            }
            elseif (!empty($data['Plugin.Title'])) {
                $title = $data['Plugin.Title'];
            }
        }

        if ($title !== null) {
            $containerElement->setTitle($title);
        }

        // Insert content element
        $containerElement = $contentElementsRepository->insert($containerElement);

        d($containerElement);
    }

    /**
     * Delete plugin
     */
    public function ajaxDelete (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \DI\Container $container,
    ) {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        // Call plugins cleanup method
        if (method_exists($plugin, 'onBeforeDelete')) {
            $container->call([ $plugin, 'onBeforeDelete' ]);
        }

        // Delete plugin
        $plugin->delete();

        return self::response('json', 200, [
            'replace' => [
                'selector' => '#contentSocketsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Page\Partials\ListSockets::class, [
                    'pageId' => $plugin->getPageId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }
    
    /**
     * 
     */
    public function ajaxSetLayout (
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Config\Config $config
    )
    {

        $get->require([ 'action', 'layoutId' ]);

        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

                
        $action = $get->get('action');
        $layout = $get->get('layoutId');

        // Set layout
        $plugin->addConfig([
            'layout' => [
                $action => $layout
            ]
        ]);


        $template = new \Frootbox\View\HtmlTemplate($plugin->getLayoutForAction($config, $action));


        if ($autoLayout = $template->getConfig('autoLayout')) {

            foreach ($autoLayout as $aAction => $alayout) {

                $plugin->addConfig([
                    'layout' => [
                        $aAction => $alayout
                    ]
                ]);
            }
        }

        $plugin->save();
        
        
        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#layoutsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Plugin\Partials\ListLayouts::class, [
                    'plugin' => $plugin,
                    'action' => $action
                ])
            ]
        ]);
    }
    
    
    /**
     *
     */
    public function ajaxSetLayoutConfig (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Config\Config $config
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        // Build view template
        $template = new \Frootbox\View\HtmlTemplate($plugin->getLayoutForAction($config, $get->get('action')));

        $variables = [ ];

        // Store layout variables
        $newVariables = $post->get('variables') ?? [];

        foreach ($template->getVariables() as $var) {

            if ($var['type'] == 'bool' and !array_key_exists($var['name'], $newVariables)) {
                $value = false;
            }
            else {
                $value = $newVariables[$var['name']] ?? $var['value'];
            }

            $variables[$var['name']] = $value;
        }

        $plugin->unsetConfig('variables.' . $get->get('action'));
        $plugin->unsetConfig('systemVariables');

        $plugin->addConfig([
            'variables' => [
                $get->get('action') => $variables,
            ],
            'systemVariables' => $post->get('systemVariables'),
        ]);

        $plugin->save();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxSwitchLayoutOptions(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        // Get layouts
        $list = [ ];

        foreach ($plugin->getPublicActions() as $action) {

            $paths = [ $plugin->getPath() . 'Layouts/' ];

            if (file_exists($path = $config->get('pluginsRootFolder'))) {

                preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($plugin), $match);

                $path .= $match[1] . '/' . $match[2] . '/' . $match[3] . '/';

                if (file_exists($path)) {
                    $paths[] = $path;
                }
            }

            foreach ($paths as $path) {

                $dir = new \Frootbox\Filesystem\Directory($path);

                if (!$dir->exists()) {
                    continue;
                }

                foreach ($dir as $file) {

                    if (!preg_match('#^' . $action . '([0-9]{1,})#i', $file, $match)) {
                        continue;
                    }

                    $files = [
                        $dir->getPath() . $file . '/View.html.twig',
                        $dir->getPath() . $file . '/View.html'
                    ];

                    $viewFile = null;

                    foreach ($files as $xpath) {
                        if (file_exists($xpath)) {
                            $viewFile = $xpath;
                            break;
                        }
                    }

                    if (empty($viewFile)) {
                        continue;
                    }

                    $list[$action][(int) $match[1]] = new \Frootbox\View\HtmlTemplate($viewFile, [
                        'templateId' => $file,
                        'number' => (int) $match[1]
                    ]);
                }
            }

            if (isset($list[$action])) {
                ksort($list[$action]);
            }
        }

        $action = $post->get('action');

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#layoutOptionsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Plugin\Partials\LayoutOptions::class, [
                    'plugin' => $plugin,
                    'layouts' => $list[$action],
                    'action' => $action,
                ]),
            ],
        ]);
    }


    /**
     * Update plugins configuration
     */
    public function ajaxUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementRepository,
    ): Response
    {
        // Validate required input
        $post->require([ 'title', 'socket' ]);

        // Fetch plugin
        $plugin = $contentElementRepository->fetchById($get->get('pluginId'));

        // Update plugin
        $plugin->setTitle($post->get('title'));
        $plugin->setInheritance($post->get('inheritance'));
        $plugin->setSocket($post->get('socket'));

        $plugin->addConfig([
            'skipLanguages' => $post->get('skipLanguages'),
        ]);

        $plugin->save();

        // Move plugin to another page
        if (!empty($post->get('pageId'))) {
            $plugin->setPageId($post->get('pageId'));
            $plugin->save();

            if (method_exists($plugin, 'onAfterMovingPlugin')) {
                $container->call([ $plugin, 'onAfterMovingPlugin' ]);
            }
        }

        d("FERTIG");
        
        return self::getResponse('json', 200);
    }


    /**
     * Update plugins configuration
     */
    public function ajaxUpdateGrid(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch element
        $element = $contentElements->fetchById($get->get('elementId'));
        $element->addConfig([
            'columns' => $post->get('columns')
        ]);
        $element->setInheritance($post->get('inheritance'));

        $element->save();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxViewAdd(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
    ): Response
    {
        $action = $get->get('action');

        // Fetch element
        $element = $contentElements->fetchById($get->get('pluginId'));

        $actualViewFile = $element->getLayoutForAction($config, $action);

        try {
            for ($layoutId = 1; $layoutId <= 99; ++$layoutId) {

                $element->addConfig([
                    'layout' => [
                        $action => ucfirst($action) . str_pad($layoutId, 2, '0',STR_PAD_LEFT)
                    ]
                ]);

                $viewFile = $element->getLayoutForAction($config, $action);
            }
        }
        catch ( \Frootbox\Exceptions\RuntimeError $e ) {
            // Ignore exception
        }

        preg_match('#^Frootbox\\\\Ext\\\\([a-z]+)\\\\([a-z]+)\\\\Plugins\\\\([a-z]+)\\\\Plugin$#i', get_class($element), $plgData);

        $newFile = $config->get('pluginsRootFolder') . $plgData[1] . '/' . $plgData[2] . '/' . $plgData[3] . '/' . ucfirst($action) . str_pad($layoutId, 2, '0',STR_PAD_LEFT) . '/View.html.twig';

        // Write new view file
        $file = new \Frootbox\Filesystem\File($newFile);
        $file->setSource(file_get_contents($actualViewFile));
        $file->write();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#layoutsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Admin\Controller\Plugin\Partials\ListLayouts::class, [
                    'plugin' => $element,
                    'action' => $action
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxViewIndividualize(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch element
        $element = $contentElements->fetchById($get->get('pluginId'));

        if (!preg_match('#^Frootbox\\\\Ext\\\\([a-z0-9]+)\\\\([a-z]+)\\\\Plugins\\\\([a-z]+)\\\\Plugin$#i', get_class($element), $plgData)) {
            throw new \Exception('Plugin namespace unrecognized.');
        }

        $viewFile = $element->getLayoutForAction($config, $get->get('action'));

        if (strpos($viewFile, $config->get('pluginsRootFolder')) !== false) {
            throw new \Frootbox\Exceptions\RuntimeError('View is already individualized.');
        }

        preg_match('#classes/Plugins/' . $plgData[3] . '/Layouts/(.*?)/View.html#i', $viewFile, $match);

        $newFile = $config->get('pluginsRootFolder') . $plgData[1] . '/' . $plgData[2] . '/' . $plgData[3] . '/' . $match[1] . '/View.html.twig';

        // Write new view file
        $file = new \Frootbox\Filesystem\File($newFile);
        $file->setSource(file_get_contents($viewFile));
        $file->write();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxViewIndividualizeStyles(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
    ): Response
    {
        // Fetch element
        $element = $contentElements->fetchById($get->get('pluginId'));

        if (!preg_match('#^Frootbox\\\\Ext\\\\([a-z0-9]+)\\\\([a-z]+)\\\\Plugins\\\\([a-z]+)\\\\Plugin$#i', get_class($element), $plgData)) {
            throw new \Exception('Plugin-Pfad nicht erkannt.');
        }

        $viewFile = $element->getLayoutForAction($config, $get->get('action'));

        preg_match('#\/([a-z0-9]+)/View.html#i', $viewFile, $match);

        $newFile = $config->get('pluginsRootFolder') . $plgData[1] . '/' . $plgData[2] . '/' . $plgData[3] . '/' . $match[1] . '/public/custom.less';

        if (file_exists($newFile)) {
            throw new \Frootbox\Exceptions\RuntimeError('Styles are already individualized.');
        }

        // Write new view file
        $file = new \Frootbox\Filesystem\File($newFile);
        $file->setSource('body .plugin.' . $plgData[1] . '.' . $plgData[2] . '.' . $plgData[3] . '.' . $match[1] . ' {' . PHP_EOL . PHP_EOL . PHP_EOL . '}' . PHP_EOL);
        $file->write();

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function container(
        \Frootbox\Admin\View $view,
        \DI\Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): Response
    {
        // Fetch plugin
        $plugin = $contentElementsRepository->fetchById($get->get('pluginId'));

        // Build admin controller
        $adminClass = substr(get_class($plugin), 0, -6) . 'Admin\\Index\\Controller';

        $pluginAdminController = new $adminClass($plugin);

        $view->set('plugin', $plugin);

        // Render plugins admin html context
        $pluginHtml = $container->call([ $pluginAdminController, 'render' ], [
            'action' => 'index',
        ]);

        return new Response(body: [
            'plugin' => $plugin,
            'pluginHtml' => $pluginHtml,
        ]);
    }

    /**
     *
     */
    public function jumpToEditing(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): ResponseInterface
    {
        // Fetch plugin
        $plugin = $contentElementsRepository->fetchById($get->get('pluginId'));

        $url = SERVER_PATH_PROTOCOL . 'cms/admin/Sitemap/index#plugin:' . $plugin->getPageId() . ':' . $plugin->getId() . ':Index:index';

        header('Location: ' . $url);
        exit;
    }

    /**
     *
     */
    public function serveStaticThumbnail(
        \Frootbox\Http\Get $get
    )
    {
        $token = $get->get('t');
        $file = $_SESSION['staticfilemap'][$token];
        $da = explode('.', $file);
        $ext = array_pop($da);

        $contentTypes = [
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'jpg' => 'image/jpg'
        ];

        header('Content-type: ' . $contentTypes[$ext]);
        readfile($file);
        exit;
    }
}
