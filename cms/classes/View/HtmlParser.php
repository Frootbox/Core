<?php
/**
 *
 */

namespace Frootbox\View;

class HtmlParser
{
    use \Frootbox\Http\Traits\UrlSanitize;

    protected $html;
    protected $container;

    /**
     *
     */
    public function __construct(string $html, \DI\Container $container)
    {
        $this->html = $html;
        $this->container = $container;
    }

    /**
     *
     */
    protected function parseEditableTags(): void
    {
        // Replace editable tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($this->html);
        $texts = $this->container->get(\Frootbox\Persistence\Content\Repositories\Texts::class);
        $files = $this->container->get(\Frootbox\Persistence\Repositories\Files::class);

        $crawler->filter('div[data-editable]')->each(function ( $element ) use ($texts, $files) {

            if ($element->getAttribute('data-parsed') !== null) {
                return;
            }

            $element->setAttribute('data-parsed', '1');

            $uid = $element->getAttribute('data-uid');

            preg_match('#^\<(.*?) #', (string) $element, $match);

            // Fetch text
            $where = [
                'uid' => $uid,
            ];

            if (MULTI_LANGUAGE) {
                $where['language'] = GLOBAL_LANGUAGE;
            }

            $text = $texts->fetchOne([
                'where' => $where,
            ]);

            if (empty($text)) {


                if (MULTI_LANGUAGE and GLOBAL_LANGUAGE != DEFAULT_LANGUAGE and $element->getAttribute('data-nolanguagefallback') === null) {

                    $text = $texts->fetchOne([
                        'where' => [
                            'uid' => $uid,
                        ],
                    ]);
                }

                if (empty($text) and !empty($element->getAttribute('data-fallback-uid'))) {

                    $uid = $element->getAttribute('data-fallback-uid');

                    // Fetch text
                    $text = $texts->fetchOne([
                        'where' => [ 'uid' => $uid ],
                    ]);

                    if (empty($text)) {

                        if (!defined('EDITING') and $element->getAttribute('data-hideempty') !== null) {
                            $element->remove();
                        }

                        return;
                    }
                }
                elseif (empty($text)) {

                    if (!defined('EDITING') and $element->getAttribute('data-hideempty') !== null) {
                        $element->remove();
                    }

                    return;
                }
            }

            $textSource = $text->getText();

            if (defined('EDITING') and !empty($textSource)) {
                $textSource .= '<p></p>';
            }

            $element->setInnerHtml($textSource);
        });

        $this->html = $crawler->saveHTML();
    }


    /**
     *
     */
    protected function parseWidgetTags(): void
    {
        // Render and replace widget tags
        preg_match_all('#<figure data-id="([0-9]{1,})"></figure>#i', $this->html, $matches);

        $widgets = $this->container->get(\Frootbox\Persistence\Content\Repositories\Widgets::class);

        foreach ($matches[0] as $index => $tagline) {

            try {
                $widget = $widgets->fetchById($matches[1][$index]);
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {

                $widget = null;
                $widgetHtml = '<figure data-ce-moveable class="widget widget-justify col-12" data-id="' . $matches[1][$index] . '">Widget #' . $matches[1][$index] . ' nicht gefunden.</figure>';
            }

            try {

                $widgetHtml = $this->container->call([ $widget, 'renderHtml' ], [
                    'action' => 'Index'
                ]);
            }
            catch ( \Exception $e ) {
                $widget = null;
                $widgetHtml = '<figure data-ce-moveable class="widget widget-justify col-12" data-id="' . $matches[1][$index] . '">' . $e->getMessage() . '</figure>';
            }

            $this->html = str_replace($tagline, $widgetHtml, $this->html);
        }
    }

    /**
     *
     */
    public function getHtml(): string
    {
        return $this->html;
    }

    /**
     *
     */
    public function parse (
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \DI\Container $container,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): string
    {
        // Inject javascript variables into head section
        $variables = [
            'serverpath' => SERVER_PATH,
            'serverpath_protocol' => SERVER_PATH_PROTOCOL,
            'request' => REQUEST
        ];

        $this->html = str_replace('</head>', '<script nonce="' . SCRIPT_NONCE . '"> var settings = ' . json_encode($variables) . ';</script>' . PHP_EOL . '</head>', $this->html);

        // Set base href
        $this->html = str_replace('</head>', '<base href="' . SERVER_PATH . '">' . PHP_EOL . '</head>', $this->html);

        // Inject publics
        if (!empty($publics = $config->get('injectPublics'))) {

            $publics = $publics->getData();
            $publics = array_unique($publics);

            foreach ($publics as $source) {

                switch (substr($source, -3)) {

                    case 'css':
                        $tag = '<link rel="stylesheet" type="text/css" href="' . $source . '" />';
                        break;

                    case 'ess':
                        $tag = '<link rel="stylesheet/less" type="text/css" href="' . $source . '" />';
                        break;

                    case '.js':
                        $tag = '<script src="' . $source . '"></script>';
                        break;
                }

                $this->html = str_replace('</head>', $tag . "\n\n</head>", $this->html);
            }
        }

        $this->parseEditables($config, $payload, $translationFactory);

        $this->rewriteTags($config, $payload, $translationFactory);

        if (preg_match_all('#\"fbx\:\/\/page\:([0-9]{1,})\#(.*?)"#', $this->html, $matches)) {

            foreach ($matches[0] as $index => $tagline) {

                try {
                    $page = $pagesRepository->fetchById($matches[1][$index]);
                }
                catch ( \Frootbox\Exceptions\NotFound $e ) {
                    continue;
                }


                $this->html = str_replace($tagline, '"' . $page->getUri() . '#' . $matches[2][$index] . '"', $this->html);
            }
        }

        if (preg_match_all('#"fbx\:\/\/page\:([0-9]{1,})"#', $this->html, $matches)) {

            foreach ($matches[0] as $index => $tagline) {

                try {
                    $page = $pagesRepository->fetchById($matches[1][$index]);
                }
                catch ( \Frootbox\Exceptions\NotFound $e ) {
                    continue;
                }

                $this->html = str_replace($tagline, '"' . $page->getUri() . '"', $this->html);
            }
        }

        $this->html = str_replace('---', '&shy;', $this->html);

        return $this->html;
    }

    /**
     *
     */
    public function parseEditables(
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\TranslatorFactory $translationFactory
    ): void
    {
        $nonStructural = [];

        if (!empty($config->get('editables'))) {

            foreach ($config->get('editables') as $editable) {

                $class = $editable['editable'] . '\\Editable';

                $editable = new $class;

                if ($editable->getType() == 'NonStructural') {
                    $nonStructural[] = $editable;
                    continue;
                }

                $this->html = $this->container->call([ $editable, 'parse' ], [ 'html' => $this->html ]);

                // div[data-editable]
                $this->parseEditableTags();

                // <figure data-id="([0-9]{1,})"></figure>
                $this->parseWidgetTags();
            }
        }

        // div[data-editable]
        $this->parseEditableTags();

        // <figure data-id="([0-9]{1,})"></figure>
        $this->parseWidgetTags();

        foreach ($nonStructural as $editable) {
            $this->html = $this->container->call([ $editable, 'parse' ], [ 'html' => $this->html ]);
        }
    }

    /**
     *
     */
    public function rewriteTags(
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\TranslatorFactory $translationFactory
    ): void
    {
        $translator = $translationFactory->get(GLOBAL_LANGUAGE);

/*
        foreach (debug_backtrace() as $trace) {

            unset($trace['args']);
            unset($trace['object']);

            p($trace);
        }

        echo "<hr />";
*/

        /*
        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($this->html);
        $crawler->filter('img[src=""]')->each(function ( $element ) use ($payload) {

            $width = $element->getAttribute('width');
            $height = $element->getAttribute('height');

            $data = $payload->clear()->addData([
                'width' => $width,
                'height' => $height
            ])->export();

            $element->setAttribute('src', SERVER_PATH . 'static/Ext/Core/Images/Dummy/render/?' . http_build_query($data));
        });


        $this->html = $crawler->saveHTML();
        */

        $html = $this->html;



        $configuration = $config;


        $cacheRevision = $configuration->get('statics.cache.revision') ?? 1;

        $css = (string) null;
        $styleFiles = [];

        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);

        $crawler->filter('link[rel="stylesheet"]')->each(function ( $element ) use (&$css, &$styleFiles, $configuration, $cacheRevision) {

            $href = $element->getAttribute('href');

            $nocache = $element->getAttribute('data-nocache');

            if ($nocache) {
                return;
            }

            $publicDir = dirname($href);

            if (substr($href, 0, 4) == 'EXT:') {

                preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $element->getAttribute('href'), $match);

                $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

                if (!class_exists($class)) {
                    $element->remove();
                    return;
                }

                $extController = new $class;

                $localPath = $extController->getAssetPath($match[3]);

            }
            elseif (substr($href, 0, 5) == 'FILE:') {

                $localPath = substr($href, 5);
            }
            elseif (substr($href, 0, 4)) {
                return;
            }
            else {

                d('Missing: ' . $href);
                $path = str_replace(SERVER_PATH, '', $href);
                $localPath = CORE_DIR . $path;
            }

            if (!file_exists($localPath)) {
                return;
            }

            $xcss = file_get_contents($localPath);

            $cssFilePath = $localPath;

            $xcss = preg_replace_callback('#url\((.*?)\)#i', function ( array $match ) use ($cssFilePath, $publicDir, $configuration, $cacheRevision) {

                $match[1] = trim($match[1], '"');
                $match[1] = trim($match[1], "'");

                if (preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $match[1], $extMatch)) {

                    $class = '\\Frootbox\\Ext\\' . $extMatch[1] . '\\' . $extMatch[2] . '\\ExtensionController';
                    $extController = new $class;

                    $localPath = $extController->getAssetPath($extMatch[3]);
                    $xpath = 'cache/public/' . $cacheRevision . '/ext/' . $extMatch[1] . '/' . $extMatch[2] . '/' . $extMatch[3];
                }
                else {

                    $localPath = realpath(dirname($cssFilePath) . '/' . preg_split('#[\#\?]#', $match[1])[0]);
                    $xpath = 'cache/public/' . $cacheRevision . '/assets/' . md5(dirname($localPath)) . '/' . basename($localPath);
                }

                $newPath = $configuration->get('filesRootFolder') . $xpath;

                if (!file_exists($newPath) and !empty($localPath)) {

                    $dir = new \Frootbox\Filesystem\Directory(dirname($newPath));
                    $dir->make();

                    copy($localPath, $newPath);
                }

                $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                $newpath = $protocol . '://' . $_SERVER['HTTP_HOST'] . $configuration->get('publicCacheDir') . $xpath;

                return str_replace($match[1], $newpath, $match[0]);
            }, $xcss);

            $css .= $xcss . PHP_EOL . PHP_EOL;

            $element->remove();
        });

        $html = $crawler->saveHTML();


        $cachefilePath = 'cache/public/' . $cacheRevision . '/css/' . md5($css) . '.css';
        $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

        if (!file_exists($cachefilePathFull)) {

            $cachefile = new \Frootbox\Filesystem\File($cachefilePathFull);
            $cachefile->setSource($css);
            $cachefile->write();
        }

        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', '<link media="all" data-nocache="1" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . '', $html);
        }
        else {
            $html = '<link media="all" data-nocache="1" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . $html;
        }

        // Parse scss
        $scss = (string) null;

        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('link[rel="stylesheet/less"]')->each(function ( $element ) use (&$scss, $configuration, $cacheRevision) {

            $href = $element->getAttribute('href');

            $publicDir = dirname($href);


            if (substr($href, 0, 4) == 'EXT:') {

                preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $element->getAttribute('href'), $match);

                $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

                if (!class_exists($class)) {
                    $element->remove();
                    return;
                }

                $extController = new $class;

                $localPath = $extController->getAssetPath($match[3]);
            }
            elseif (substr($href, 0, 5) == 'FILE:') {

                $href = str_replace('%5C', '\\', $href);
                $localPath = substr($href, 5);
            }
            elseif (substr($href, 0, 4) == 'http') {

                $skipFile = true;

                $arrContextOptions = [
                    "ssl" => [
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ],
                ];

                $css = file_get_contents($href, false, stream_context_create($arrContextOptions));


                $cssFilePath = null;
            }
            else {

                $path = str_replace(SERVER_PATH, '', $href);
                $localPath = CORE_DIR . $path;
            }

            if (empty($skipFile)) {

                if (!file_exists($localPath)) {

                    p('Missing: ' . $localPath);

                    return;
                }

                $css = file_get_contents($localPath);
                $cssFilePath = $localPath;
            }

            $css = preg_replace_callback('#url\((.*?)\)#i', function ( array $match ) use ($cssFilePath, $publicDir, $configuration, $cacheRevision) {

                $match[1] = trim($match[1], "'\"");

                if ($match[1][0] == '#' or substr($match[1], 0, 8) == 'https://' or substr($match[1], 0, 5) == 'data:') {
                    return $match[0];
                }

                if (preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $match[1], $extMatch)) {

                    $class = '\\Frootbox\\Ext\\' . $extMatch[1] . '\\' . $extMatch[2] . '\\ExtensionController';
                    $extController = new $class;

                    $localPath = $extController->getAssetPath($extMatch[3]);
                    $xpath = 'cache/public/' . $cacheRevision . '/ext/' . $extMatch[1] . '/' . $extMatch[2] . '/' . $extMatch[3];
                }
                else {

                    $localPath = realpath(dirname($cssFilePath) . '/' . preg_split('#[\#\?]#', $match[1])[0]);

                    $xpath = 'cache/public/' . $cacheRevision . '/assets/' . md5(dirname($localPath)) . '/' . basename($localPath);
                }

                if (empty($localPath) or !file_exists($localPath)) {

                    d('m' . $cssFilePath);
                    d(dirname($cssFilePath) . '/' . preg_split('#[\#\?]#', $match[1])[0]);
                }

                $newPath = $configuration->get('filesRootFolder') . $xpath;

                if (!file_exists($newPath)) {

                    $dir = new \Frootbox\Filesystem\Directory(dirname($newPath));
                    $dir->make();

                    copy($localPath, $newPath);
                }

                $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
                $newpath = $protocol . '://' . $_SERVER['HTTP_HOST'] . $configuration->get('publicCacheDir') . $xpath;

                return str_replace($match[1], $newpath, $match[0]);
            }, $css);


            $scss .= $css . PHP_EOL . PHP_EOL;

            $element->remove();
        });

        $html = $crawler->saveHTML();

        if (preg_match_all('#<style type="text/less">(.*?)</style>#is', $html, $matches)) {

            foreach($matches[0] as $index => $snippet) {

                $scss = $matches[1][$index] . PHP_EOL . PHP_EOL . $scss;

                $html = str_replace($snippet, '', $html);
            }
        }

        // Compile css
        $cachefilePath = 'cache/public/' . $cacheRevision . '/css/' . md5($scss) . '-compiled.css';
        $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

        if (!file_exists($cachefilePathFull)) {

            $compiler = new \ScssPhp\ScssPhp\Compiler();

            $cachefile = new \Frootbox\Filesystem\File($cachefilePathFull);
            $cachefile->setSource($compiler->compile($scss));
            $cachefile->write();
        }

        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', '<link media="all" data-nocache="1" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . '</head>', $html);
        }
        else {
            $html = '<link media="all" data-nocache="1" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . $html;
        }

        // Combine css files
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $css = (string) null;

        $crawler->filter('link[href^="' . $configuration->get('publicCacheDir') . '"]')->each(function ( $element ) use (&$css, $configuration, $cacheRevision) {

            $href = $element->getAttribute('href');

            $path = str_replace($configuration->get('publicCacheDir'), '', $href);
            $file = FILES_DIR . $path;

            $css .= PHP_EOL . PHP_EOL . file_get_contents($file);

            $element->remove();
        });

        // Clear source map references
        $css = preg_replace('#\/\*\# sourceMappingURL=(.*?).map \*\/#i', '', $css);

        $html = $crawler->saveHTML();

        // Write cache file
        $cachefilePath = 'cache/public/' . $cacheRevision . '/css/' . md5($css) . '-final.css';
        $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;


        /*
        // Insert preloads
        if (preg_match_all('#url\(' . SERVER_PATH_PROTOCOL . '([a-z0-9\.\-\/\_]+)\.(ttf|eot|woff|woff2)\)#i', $css, $matches)) {

            $preHtml = (string) null;
            $check = [];

            foreach ($matches[0] as $index => $hit) {

                $path = SERVER_PATH_PROTOCOL . $matches[1][$index] . '.' . $matches[2][$index];

                if (in_array($path, $check)) {
                    continue;
                }

                $preHtml .= PHP_EOL . '<link rel="preload" as="font" href="' . $path . '" type="font/' . $matches[2][$index] . '" crossorigin="anonymous">';
                $check[] = $path;
            }

            $html = str_replace('</head>', $preHtml . '</head>', $html);
        }
        */

        if (!file_exists($cachefilePathFull)) {

            $cachefile = new \Frootbox\Filesystem\File($cachefilePathFull);
            $cachefile->setSource($css);
            $cachefile->write();
        }

        if (strpos($html, '</head>') !== false) {
            $html = str_replace('</head>', '<link media="all" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . '</head>', $html);
        }
        else {
            $html = '<link media="all" rel="stylesheet" type="text/css" href="' . $configuration->get('publicCacheDir') . $cachefilePath . '" />' . PHP_EOL . $html;
        }

        // Compile javascript
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);

        $minifier = new \Frootbox\View\Minifier\JsMinifier;
        $scripts = [ ];
        $scriptChecker = [];

        $crawler->filter('script[src^="EXT:"], script[src^="FILE:"]')->each(function ( $element ) use ($minifier, &$scripts, &$scriptChecker) {

            $src = $element->getAttribute('src');

            if (substr($src, 0, 5) == 'FILE:') {

                $localPath = substr(urldecode($src), 5);
            }
            else {

                preg_match('#^EXT:(.*?)\/(.*?)\/(.*?)$#', $src, $match);

                $class = '\\Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\ExtensionController';

                if (!class_exists($class)) {
                    return;
                }

                $extController = new $class;

                $localPath = $extController->getAssetPath($match[3]);
            }

            if (!file_exists($localPath)) {
                return;
            }

            if (in_array($localPath, $scriptChecker)) {
                $element->remove();
                return;
            }

            $scriptChecker[] = $localPath;

            if (empty($element->getAttribute('data-untouched'))) {
                $minifier->add(file_get_contents($localPath));
            }
            else {
                $scripts[] = $localPath;
            }

            $element->remove();
        });

        $html = $crawler->saveHTML();

        preg_match_all('#<translate key="(.*?)"></translate>#', $html, $matches);

        foreach ($matches[0] as $index => $tagline) {
            $html = str_replace($tagline, $translator->translate($matches[1][$index]), $html);
        }

        // Generate cachefile
        $cacheFilePath = 'cache/public/' . $cacheRevision . '/js/' . $minifier->getHash() . '.min.js';

        $cacheFile = $configuration->filesRootFolder . $cacheFilePath;

        if (!file_exists($cacheFile)) {

            $file = new \Frootbox\Filesystem\File($cacheFile);
            $file->write();

            $minifier->minify($cacheFile);
        }

        if (strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script async src="' . $configuration->get('publicCacheDir') . $cacheFilePath . '"></script>' . PHP_EOL . '</body>', $html);
        }
        else {
            $html = '<script async src="' . $configuration->get('publicCacheDir') . $cacheFilePath . '"></script>' . PHP_EOL . $html;
        }

        foreach ($scripts as $script) {

            $cacheFilePath = 'cache/public/' . $cacheRevision . '/js/' . rand(1000, 9999) . '-' . basename($script);
            $cacheFile = $configuration->filesRootFolder . $cacheFilePath;
            $file = new \Frootbox\Filesystem\File($cacheFile);
            $file->setSource(file_get_contents($script));
            $file->write();

            $html = str_replace('</body>', '<script async data-untouched="1" src="' . $configuration->get('publicCacheDir') . $cacheFilePath . '"></script>' . PHP_EOL . '</body>', $html);
        }


        // Combine javascript
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $js = (string) null;

        $crawler->filter('script[src^="' . $configuration->get('publicCacheDir') . '"]')->each(function ( $element ) use (&$js, $configuration) {

            $src = $element->getAttribute('src');


            if (!empty($element->getAttribute('data-untouched'))) {
                return;
            }

            $path = str_replace($configuration->get('publicCacheDir'), '', $src);
            $file = FILES_DIR . $path;

            $js .= PHP_EOL . PHP_EOL . file_get_contents($file);

            $element->remove();
        });


        // Clear source map references
        $js = preg_replace('#\/\/\# sourceMappingURL=(.*?).map#i', '', $js);

        $html = $crawler->saveHTML();


        // Write cache file
        $cachefilePath = 'cache/public/' . $cacheRevision . '/js/' . md5($js) . '-final.js';
        $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

        if (!file_exists($cachefilePathFull)) {

            $cachefile = new \Frootbox\Filesystem\File($cachefilePathFull);
            $cachefile->setSource($js);
            $cachefile->write();
        }

        if (strpos($html, '</body>') !== false) {
            $html = str_replace('</body>', '<script async src="' . SERVER_PATH_PROTOCOL . $configuration->get('publicCacheDir') . $cachefilePath . '"></script>' . PHP_EOL . '</body>', $html);
        }
        else {
            $html = '<script async src="' . SERVER_PATH_PROTOCOL . $configuration->get('publicCacheDir') . $cachefilePath . '"></script>' . PHP_EOL . $html;
        }

        preg_match_all('#\"EXT:(.*?)/(.*?)/(.*?)\"#', $html, $matches);

        foreach ($matches[0] as $index => $originalSource) {

            $class = '\\Frootbox\\Ext\\' . $matches[1][$index] . '\\' . $matches[2][$index] . '\\ExtensionController';

            if (!class_exists($class)) {
                continue;
            }

            $extController = new $class;

            $localPath = $extController->getAssetPath($matches[3][$index]);

            if (!file_exists($localPath)) {
                continue;
            }

            $cachefilePath = 'cache/public/' . $cacheRevision . '/ext/' . $matches[1][$index] . '/' . $matches[2][$index] . '/' . $matches[3][$index];
            $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

            if (!file_exists($cachefilePathFull)) {

                $file = new \Frootbox\Filesystem\File($cachefilePathFull);
                $file->write();

                copy($localPath, $cachefilePathFull);
            }

            $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $newpath = $protocol . '://' . $_SERVER['HTTP_HOST'] . $configuration->get('publicCacheDir') . $cachefilePath;

            $html = str_replace($originalSource, $newpath, $html);
        }

        // Parse direct file pointers in images
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('img[src^="FILE:"]')->each(function ( $element ) use ($configuration, $cacheRevision) {

            $src = rawurldecode(substr($element->getAttribute('src'), 5));

            $cachefilePath = 'cache/public/' . $cacheRevision . '/assets/' . md5($src) . '-' . basename($src);
            $cachefilePathFull = $configuration->get('filesRootFolder') . $cachefilePath;

            if (!file_exists($cachefilePathFull)) {

                $file = new \Frootbox\Filesystem\File($cachefilePathFull);
                $file->write();

                copy($src, $cachefilePathFull);
            }

            $element->setAttribute('src', $configuration->get('publicCacheDir') . $cachefilePath);
        });

        $html = $crawler->saveHTML();

        $this->html = $html;
    }
}
