<?php 
/**
 * 
 */

namespace Frootbox\Persistence;

abstract class AbstractPlugin extends AbstractRow
{
    use Traits\Config;
    use Traits\Uid;
    use Traits\DummyImage;
    use Traits\Visibility;

    protected $table = 'content_elements';
    // protected $model = Content\Repositories\ContentElements::class;
    protected $model = Repositories\ContentElements::class;

    protected $attributes = [ ];

    protected $page = null;
    protected $isFirst = false;

    protected string $currentAction;
    protected $overrideTemplate;
    protected $isContainerPlugin = false;
    protected $icon = 'fas fa-puzzle-piece';

    /**
     * @param $configPath
     * @return array|mixed|null
     */
    public function getGlobalConfig($configPath = null): mixed
    {
        $config = new \Frootbox\Config\Config(CORE_DIR . 'localconfig.php');
        $extSections = $this->parseClassName();

        $path = 'Ext.' . $extSections['vendor'] . '.' . $extSections['extension'] . '.' . $extSections['plugin'];

        if ($configPath !== null) {
            $path .= '.' . $configPath;
        }

        return $config->get($path);
    }

    /**
     * Parse classname and extract vendor, extension and plugin names
     *
     * @return array
     */
    private function parseClassName(): array
    {
        // Parse class name
        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

        return [ 'vendor' => $match[1], 'extension' => $match[2], 'plugin' => $match[3] ];
    }

    /**
     *
     */
    protected function setOverrideTemplate(string $template): void
    {
        $this->overrideTemplate = $template;
    }

    /**
     * @return array|null
     */
    public function getAdditionalLanguageFiles(string $language): ?array
    {
        return null;
    }

    /**
     *
     */
    public function getAttribute ( $attribute, $default = null ) {

        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }

        if ($default !== null) {
            return $default;
        }

        throw new \Frootbox\Exceptions\BadArgument('Missing attribute ' . $attribute);
    }

    /**
     * @param string $layout
     * @return string
     */
    public function getBaseActionView(string $layout): string
    {
        return $this->getPath() . 'Layouts/' . $layout . '/View.html.twig';
    }

    /**
     *
     */
    public function getBaseLayout($action)
    {
        $layout = $this->overrideTemplate ?? $this->config['layout'][$action] ?? ucfirst($action) . '01';

        $paths = $this->getPath() . 'Layouts' . DIRECTORY_SEPARATOR . $layout . DIRECTORY_SEPARATOR . 'View.html.twig';

        return $paths;
    }

    /**
     * @return string
     */
    public function getCurrentAction(): string
    {
        return $this->currentAction;
    }

    /**
     *
     */
    public function getLayoutForAction(
        \Frootbox\Config\Config $config,
        $action,
        \Frootbox\View\Engines\Interfaces\Engine $view = null
    ): string
    {
        $layout = $this->overrideTemplate ?? $this->config['layout'][$action] ?? ucfirst($action) . '01';

        $paths = [ ];

        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

        if (!empty($config->get('pluginsRootFolder'))) {
            $paths[] = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $layout . '/View.html.twig';
            $paths[] = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $layout . '/View.html';
        }

        $paths[] = $this->getPath() . 'Layouts' . DIRECTORY_SEPARATOR . $layout . DIRECTORY_SEPARATOR . 'View.html.twig';
        $paths[] = $this->getPath() . 'Layouts' . DIRECTORY_SEPARATOR . $layout . DIRECTORY_SEPARATOR . 'View.html';

        if ($view !== null) {

            foreach ($paths as $path) {

                if (!file_exists($path)) {
                    continue;
                }

                $view->addPath(dirname(dirname($path)));
            }
        }

        foreach ($paths as $layoutFile) {

            if (file_exists($layoutFile)) {
                return $layoutFile;
            }
        }

        throw new \Frootbox\Exceptions\RuntimeError('View file ' . $layout . ' missing.');
    }

    /**
     * List available layouts for certain action
     */
    public function getLayoutsForAction(\Frootbox\Config\Config $config, string $action)
    {
        // Get layouts
        $list = [ ];

        $paths = [ $this->getPath() . 'Layouts/' ];

        if (file_exists($path = $config->get('pluginsRootFolder'))) {

            preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

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

                if (!preg_match('#' . $action . '([0-9]{1,})#i', $file, $match)) {
                    continue;
                }

                if (!file_exists($dir->getPath() . $file . '/View.html')) {
                    continue;
                }

                $list[(int) $match[1]] = new \Frootbox\View\HtmlTemplate($dir->getPath() . $file . '/View.html', [
                    'templateId' => $file,
                    'number' => (int) $match[1]
                ]);
            }
        }

        ksort($list);

        return $list;
    }

    /**
     * Get plugins page
     */
    public function getPage(): \Frootbox\Persistence\Page
    {
        if ($this->page === null) {

            // Fetch page
            $pages = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
            $this->page = $pages->fetchById($this->getPageId());
        }

        return $this->page;
    }

    /**
     * 
     */
    abstract public function getPath(): string;

    /**
     * 
     */
    public function getAdminUri($controller, $action, array $parameters = null): string
    {
        if (!empty($this->page)) {
            $parameters['pageId'] = $this->page->getId();
        }

        $parameters['pluginId'] = $this->getId();
        $parameters['controller'] = $controller;
        $parameters['action'] = $action;
        
        $url = SERVER_PATH . 'cms/admin/Plugin/ajaxPanelAction?' . http_build_query($parameters) . '&' . SID;
        
        return $url;
    }

    /**
     *
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return string[]
     */
    public function getPublicActions(): array
    {
        return $this->publicActions ?? [ 'index' ];
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    public function getResourcePathFromBaseExtension(string $path): string
    {
        $extensionRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Extensions::class);
        $extensions = $extensionRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        foreach ($extensions as $extension) {

            $controller = $extension->getExtensionController();

            if ($controller->getType() == 'Generic') {
                continue;
            }

            $filePath = $controller->getPath() . 'resources/' . $path;

            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        throw new \Exception(sprintf('File not found: %s', $path));
    }

    /**
     *
     */
    public function getTitleDefault(): ?string
    {
        if (!file_exists($langfile = $this->getPath() . 'resources/private/language/de-DE.php')) {
            return null;
        }

        $data = require $langfile;

        if (empty($data['Plugin.Title'])) {
            return null;
        }

        return $data['Plugin.Title'];
    }

    /**
     *
     */
    public function getTranslator (
        \Frootbox\TranslatorFactory $factory,
        $language = null
    ) {
        // Generate translator
        $translator = $factory->get($language ?? GLOBAL_LANGUAGE);

        $translator->setScope(str_replace('\\', '.', substr(substr(get_class($this), 0, -7), 13)));

        return $translator;
    }


    /**
     *
     */
    public function getUri(
        $action = null,
        array $payload = null,
        array $options = null
    ): string
    {
        // Fetch page if not set yet
        if (empty($this->page)) {

            // Fetch page
            $pages = $this->db->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
            $this->page = $pages->fetchById($this->getPageId());
        }

        if ($action == 'index' and empty($payload)) {
            return $this->page->getUri($options);
        }

        $payload['action'] = $action;

        $data = [
            'p' => [
                $this->getId() => $payload
            ]
        ];

        return $this->page->getUri($options) . '?' . http_build_query($data);
    }

    /**
     * @param string $action
     * @param array|null $payloadData
     * @param array|null $options
     * @return string
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function getActionUri (
        string $action,
        array $payloadData = null,
        array $options = null
    ): string
    {
        $payload = new \Frootbox\Payload;

        // Fetch page if not set yet
        if (empty($this->page)) {

            // Fetch page
            $pages = $this->db->getRepository(\Frootbox\Persistence\Repositories\Pages::class);
            $this->page = $pages->fetchById($this->getPageId());
        }

        if ($action == 'index' and empty($payloadData)) {
            return $this->page->getUri($options);
        }

        $payloadData['action'] = $action;

        $data = [
            'p' => [
                $this->getId() => $payloadData
            ]
        ];

        // Prepare payload
        $payload->clear();
        $payload->addData($data);

        $uri = $this->page->getUri($options);

        $info = parse_url($uri);

        return $this->page->getUri($options) . (!empty($info['query']) ? '&' : '?') . http_build_query($payload->export()) . '&' . SID;
    }

    /**
     *
     */
    public function getPageUri(string $page, string $action, array $payload = null ): string
    {
        $url = SERVER_PATH_PROTOCOL . 'static/' . $page . '/' . $action;

        if (!empty($payload)) {
            $url .= '?' . http_build_query($payload);
        }

        return $url;
    }

    /**
     *
     */
    public function getUriAjax($action, array $payload = null, array $options = null ): string
    {
        $payload['pluginId'] = $this->getId();
        $payload['action'] = $action;


        $url = 'ajax/?' . http_build_query($payload) . '&' . SID;

        if (!empty($options['absolute'])) {
            $url = SERVER_PATH_PROTOCOL . $url;
        }
        else {
            $url = SERVER_PATH . $url;
        }

        return $url;
    }

    /**
     *
     */
    public function getAjaxUri($action, array $payload = null, array $options = null ): string
    {
        return $this->getUriAjax($action, $payload, $options);
    }

    /**
     *
     */
    public function hasAttribute($attribute): bool
    {
        return isset($this->attributes[$attribute]);
    }

    /**
     *
     */
    public function importConfig(\Frootbox\Config\Config $config): void
    {
        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

        $path = 'Plugins.' . $match[1] . '.' . $match[2] . '.' . $match[3];

        if ($xconfig = $config->get($path)) {
            $this->config = array_replace_recursive($this->config, $xconfig->getData());
        }
    }

    /**
     *
     */
    public function injectScript(\Frootbox\Config\Config $config, $path): string
    {
        return '<script src="FILE:' . $this->getPath() . 'Layouts/' . $path . '"></script>';
    }

    /**
     *
     */
    public function injectScss (
        \Frootbox\Config\Config $config,
        $path
    ): string
    {
        if (!empty($config->get('pluginsRootFolder'))) {
            preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

            if (file_exists($ppath = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $path)) {
                return '<link type="text/css" rel="stylesheet/less" href="FILE:' .$ppath . '" />';
            }
        }

        return '<link type="text/css" rel="stylesheet/less" href="FILE:' . $this->getPath() . 'Layouts/' . $path . '" />';
    }

    /**
     *
     */
    public function getPublicAsset(
        \Frootbox\Config\Config $config,
        $asset
    ): string
    {
        $viewFile = $this->getLayoutForAction($config, $this->getAttribute('action'));

        $file = dirname($viewFile) . '/public/' . $asset;

        if (file_exists($file)) {
            return $file;
        }

        d($file);

        if (!empty($config->get('pluginsRootFolder'))) {
            preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

            d($config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $asset);

            if (file_exists($ppath = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $path)) {
                return '<link type="text/css" rel="stylesheet/less" href="FILE:' .$ppath . '" />';
            }
        }

        d("OKKK");
        return '<link type="text/css" rel="stylesheet/less" href="FILE:' . $this->getPath() . 'Layouts/' . $path . '" />';
    }

    /**
     * Check if plugin is container plugin
     */
    public function isContainer(): bool
    {
        return $this->isContainerPlugin;
    }

    /**
     *
     */
    public function isFirst(): bool
    {
        return ($this->isFirst OR $this->getConfig('systemVariables.firstContent'));
    }

    /**
     *
     */
    public function log(string $code, array $data = null): void
    {
        if (empty($_SESSION['user']['id'])) {
            throw new \Exception('Missing user id');
        }

        preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

        $code = $match[1] . '.' . $match[2] . '.Plugins.' . $match[3] . '.Logs.' . $code;

        // Insert new log
        $systemLogsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\SystemLogs::class);
        $log = $systemLogsRepository->insert(new \Frootbox\Persistence\SystemLog([
            'userId' => $_SESSION['user']['id'],
            'log_code' => $code,
            'config' => $data,
        ]));
    }

    /**
     * 
     */
    public function renderHtml(
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        $action,
        $variables = null,
    ): string
    {

        try {

            // Get layout file
            $layoutFile = $this->getLayoutForAction($config, $action, $view);

            $layout = $this->overrideTemplate ?? $this->config['layout'][$action] ?? ucfirst($action) . '01';

            if (!file_exists($layoutFile)) {
                die("noo file");
            }

            $view->addPath(dirname($layoutFile) . '/');
            $view->set('plugin', $this);
            $view->set('pluginAction', $action);

            $template = new \Frootbox\View\HtmlTemplate($layoutFile, [
                'variables' => $this->config['variables'][$action] ?? [],
            ]);

            $payload = [];

            foreach ($template->getVariables() as $variable) {
                $payload[$variable['name']] = $variable['value'];
            }

            if (!empty($systemVariables = $this->getConfig('systemVariables'))) {
                $payload['systemVariables'] = $systemVariables;
            }

            $view->set('variables', $payload);
            $view->set('viewFile', $layoutFile);
            $view->set('viewFolder', dirname($layoutFile) . DIRECTORY_SEPARATOR);

            if (empty($variables)) {
                $variables = [];
            }

            $html = $view->render($layoutFile, $variables);


            $paths = $this->getPath() . 'Layouts' . DIRECTORY_SEPARATOR . $layout . DIRECTORY_SEPARATOR . 'View.html.twig';

            // Auto inject layouts scss
            $standardsLayoutScss = null;
            if (file_exists($file = dirname($this->getBaseLayout($action)) . '/public/standards.less')) {
                $standardsLayoutScss = $file;
            }

            // Auto inject default layouts scss if view is individualized
            if (strpos($layoutFile, $this->getPath()) === false) {

                $file = dirname($layoutFile) . '/public/standards.less';

                if (file_exists($file)) {
                    $standardsLayoutScss = $file;
                }
            }

            if (!empty($standardsLayoutScss)) {
                $html = '<link type="text/css" rel="stylesheet/less" href="FILE:' . $standardsLayoutScss . '" />' . PHP_EOL . $html;
            }

            // Auto inject default javascript
            $files = [];

            // Check custom folder
            $files[] = dirname($layoutFile) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'init.js';

            // Check base folder if layout is extended and original publics are requested
            if (!empty($template->getConfig('injectPublics'))) {
                $files[] = $this->getPath() . 'Layouts' . DIRECTORY_SEPARATOR . $template->getConfig('injectPublics') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'init.js';
            }

            // Check actual layouts folder
            $files[] = $this->getPath() . 'Layouts/' . $layout . '/public/init.js';

            foreach ($files as $file) {

                if (file_exists($file)) {
                    $html = '<script src="FILE:' . $file . '"></script>' . PHP_EOL . $html;
                    break;
                }
            }

            preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Plugins\\\\(.*?)\\\\Plugin$#', get_class($this), $match);

            if (!empty($config->get('pluginsRootFolder'))) {

                // Auto inject custom styles
                preg_match('#[\/\\\\](?P<layout>[a-z0-9]+)[\/\\\\]View.html#i', $layoutFile, $matchx);

                $path = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $matchx['layout'] . '/public/custom.less';

                if (file_exists($path)) {
                    $html = '<link type="text/css" rel="stylesheet/less" href="FILE:' . $path . '" />' . PHP_EOL . $html;
                }

                // Auto inject javascripts
                $path = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $matchx['layout'] . '/public/init.js';

                if (file_exists($path)) {
                    $html = '<script src="FILE:' . $path . '"></script>' . PHP_EOL . $html;
                }

                // Auto inject custom javascripts
                $path = $config->get('pluginsRootFolder') . $match[1] . '/' . $match[2] . '/' . $match[3] . '/' . $matchx['layout'] . '/public/custom.js';

                if (file_exists($path)) {
                    $html = '<script src="FILE:' . $path . '"></script>' . PHP_EOL . $html;
                }
            }
        }
        catch ( \Exception $e ) {

            if (get_class($e) == \Twig\Error\RuntimeError::class) {
                $e = $e->getPrevious();
            }

            $html = '<div style="border: 1px solid red; padding: 10px;"><b>' . $e->getMessage() . '</b>';

            if (IS_LOGGED_IN) {

                foreach ($e->getTrace() as $trace) {

                    if (!empty($trace['class'])) {
                        $html .= '<br />' . $trace['class'] . '::' . $trace['function'] . '()';
                    }

                    if (!empty($trace['file'])) {
                        $html .= ' <span style="color: #CCC;">in Line ' . $trace['line'] . ' @ ' . str_replace(CORE_DIR, '', $trace['file']) . '</span>';
                    }
                }
            }

            $html .= '</div>';
        }

        return $html;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes ( array $attributes ) : AbstractPlugin {

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @param string $action
     * @return void
     */
    public function setCurrentAction(string $action): void
    {
        $this->currentAction = $action;
    }

    /**
     * Mark element as first
     */
    public function setFirst ( ): AbstractPlugin
    {
        // Mark as first
        $this->isFirst = true;

        return $this;
    }

    /**
     *
     */
    public function setPage ( \Frootbox\Persistence\Page $page ) {

        $this->page = $page;

        return $this;
    }
}