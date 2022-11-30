<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Block\Admin;

use DI\Container;
use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxCreate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \DI\Container $container,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view,
    ): Response
    {
        // Validate required input
        $post->require([ 'block' ]);

        if ($post->get('block') != 'fromClipboard') {

            $data = explode('-', $post->get('block'));

            // Compose new block
            $block = new \Frootbox\Persistence\Content\Blocks\Block([
                'pageId' => $get->get('pageId'),
                'uid' => $get->get('uid'),
                'vendorId' => $data[0],
                'extensionId' => $data[1],
                'blockId' => $data[2],
            ]);

            $extensionController = $block->getExtensionController();

            $classPath = $extensionController->getPath() . 'classes/Blocks/' . $block->getBlockId() . '/Block.php';

            if (file_exists($classPath)) {

                if (preg_match('#\/([a-z0-9]+)\/([a-z0-9]+)\/classes\/Blocks\/([a-z0-9]+)\/Block.php$#i', $classPath, $match)) {

                    $className = 'Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\Blocks\\' . $match[3] . '\\Block';
                    $block->setClassName($className);
                }
            }
            else {

                $source = file_get_contents(dirname($classPath) . '/Block.html.twig');

                if (preg_match('#extends: ([a-z]+)/([a-z]+)/([a-z]+)\s#i', $source, $match)) {

                    $className = '\\Frootbox\\Ext\\' . $match[1] .  '\\' . $match[2] . '\\ExtensionController';

                    if (!class_exists($className)) {
                        throw new \Exception('Erweiterung ' . $match[1] .  '/' . $match[2] . ' fehlt.');
                    }

                    $controller = new $className;

                    $classPath = $controller->getPath() . 'classes/Blocks/' . $match[3] . '/Block.php';

                    if (file_exists($classPath)) {

                        $className = 'Frootbox\\Ext\\' . $match[1] . '\\' . $match[2] . '\\Blocks\\' . $match[3] . '\\Block';
                        $block->setClassName($className);
                    }
                }
            }

            // Insert new block
            $block = $blocksRepository->insert($block);
            $block = $blocksRepository->fetchById($block->getId());
        }
        else {

            if (empty($_SESSION['editmode']['editables']['block']['copy'])) {
                throw new \Exception('Clipboard empty.');
            }

            // Fetch block
            $block = $blocksRepository->fetchById($_SESSION['editmode']['editables']['block']['copy']);

            // Clone reference
            $newBlock = clone $block;
            $newBlock->setOrderId(null);
            $newBlock->setUid($get->get('uid'));
            $newBlock->setPageId($get->get('pageId'));

            $newBlock = $blocksRepository->insert($newBlock);

            // Clone contents
            $cloningMachine->cloneContentsForElement($newBlock, $block->getUidBase());

            $block = $newBlock;

            if (empty($post->get('keepClipboard'))) {
                unset($_SESSION['editmode']['editables']['block']['copy']);
            }
        }

        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch();
        $scss = (string) null;

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath();

            $scssFile = $path . 'resources/public/css/styles-variables.less';

            if (file_exists($scssFile)) {
                $scss .= PHP_EOL . file_get_contents($scssFile);
            }
        }

        $blockHtml = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $blockHtml;

        $view->set('view', $view);
        $view->set('page', $block->getPage());

        $parser = new \Frootbox\View\HtmlParser($blockHtml, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $get->get('uid'),
            'html' => $html
        ]);
    }

    /**
     *
     */
    public function ajaxBlockCopy(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        $_SESSION['editmode']['editables']['block']['copy'] = $block->getId();

        return new Response('json');
    }

    /**
     *
     */
    public function ajaxBlockDelete(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    )
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        $block->delete();

        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch();
        $scss = (string) null;

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath();

            $scssFile = $path . 'resources/public/css/styles-variables.less';

            if (file_exists($scssFile)) {
                $scss .= PHP_EOL . file_get_contents($scssFile);
            }
        }

        $view->set('view', $view);
        $blockHtml = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $blockHtml;

        $parser = new \Frootbox\View\HtmlParser($blockHtml, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'blockId' => $block->getId(),
            'uid' => $block->getUidRaw(),
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function ajaxBlockSwitch(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        $block->visibilityPush();

        return self::getResponse('json', 200, [
            'blockId' => $block->getId(),
            'uid' => $block->getUidRaw(),
            'visibility' => $block->getVisibilityString(),
        ]);
    }

    /**
     *
     */
    public function ajaxModalCompose(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): Response
    {
        $attributes = $get->get('attributes') ?? [];
        $restricted = $attributes['restrict'] ?? null;

        $list = [];
        $loop = 0;
        $extLoop = 0;

        // Fetch extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        foreach ($result as $extension) {

            $extController = $extension->getExtensionController();

            ++$extLoop;
            $loopKey = $extController->getType() == 'Template' ? $extLoop + 200 : $extLoop + 100;

            $dir = new \Frootbox\Filesystem\Directory($extController->getPath() . 'classes/Blocks/');

            $extList = [
                'extension' => $extension->getVendorId() . '/' . $extension->getExtensionId(),
                'blocks' => [],
            ];

            foreach ($dir as $file) {

                if ($file[0] == '_') {
                    continue;
                }

                if (!file_exists($dir->getPath() . $file . '/Block.html.twig')) {
                    continue;
                }

                $blockTemplate = new \Frootbox\View\Blocks\BlockTemplate($dir->getPath() . $file);

                if (!empty($restricted)) {

                    $restrictions = $blockTemplate->getConfig('restricted');

                    if (empty($restrictions) or !in_array($restricted, $restrictions)) {
                        continue;
                    }
                }

                $source = file_get_contents($dir->getPath() . $file . '/Block.html.twig');

                if (preg_match('#override:#', $source)) {
                    continue;
                }

                $section = 'Default';

                if (preg_match('#section: (.*?)\s#', $source, $match)) {
                    $section =  $match[1];
                }

                $extList['blocks'][$section][$blockTemplate->getTitle() . ++$loop] = [
                    'blockId' => $file,
                    'vendorId' => $extension->getVendorId(),
                    'extensionId' => $extension->getExtensionId(),
                    'template' => $blockTemplate,
                ];
            }

            if (!empty($extList['blocks'])) {

                foreach ($extList['blocks'] as $section => $blocks) {
                    ksort($blocks);
                    $extList['blocks'][$section] = $blocks;
                }

                $list[$loopKey] = $extList;
            }
        }

        krsort($list);


        // Show blocks clipboard
        if (!empty($_SESSION['editmode']['editables']['block']['copy'])) {

            try {
                // Fetch block
                $block = $blocksRepository->fetchById($_SESSION['editmode']['editables']['block']['copy']);

                if (empty($block->getClassName())) {
                    $path = $block->getPathFromConfig($config);
                }
                else {
                    $path = $block->getPath();
                }

                $copiedTemplate = new \Frootbox\View\Blocks\BlockTemplate($path);
            }
            catch ( \Exception $e ) {
                // Ignore any errors and clear clipboard
                unset($_SESSION['editmode']['editables']['block']['copy']);
            }
        }

        return self::getResponse('html', 200, [
            'uid' => $get->get('uid'),
            'blocksList' => $list,
            'copied' => $copiedTemplate ?? null,
        ]);
    }

    /**
     *
     */
    public function ajaxModalConfig (
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        $viewFile = $block->getViewFile();

        $template = new \Frootbox\View\HtmlTemplate($viewFile, [
            'variables' => $block->getConfig('template.variables'),
        ]);

        return self::getResponse('html', 200, [
            'block' => $block,
            'template' => $template,
        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit (
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Admin\View $view,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $frontView,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        if (empty($block->getClassName())) {
            $path = $block->getPathFromConfig($config);
        }
        else {
            $path = $block->getPath();
        }

        $viewFile = $path . 'Block.html.twig';

        $template = new \Frootbox\View\HtmlTemplate($viewFile, [
            'variables' => $block->getConfig('template.variables'),
        ]);

        if (empty($block->getClassName())) {
            return self::getResponse('html', 200, [
                'block' => $block,
                'template' => $template,
            ]);
        }

        $adminController = $block->getAdminController();

        $action = $get->get('blockAction') ?? 'index';

        $response = $container->call([ $adminController, $action . 'Action' ]);

        if ($response->getType() == 'json') {
            http_response_code(200);
            header('Content-Type: application/json');
            die($response->getBody());
        }

        if (empty($response->getBodyData()) and !empty($response->getBody())) {
            return $response;
        }

        $viewFile = $adminController->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';


        if (!empty($response->getBodyData())) {

            foreach ($response->getBodyData() as $key => $value) {
                $view->set($key, $value);
            }
        }

        $html = $view->render($viewFile, null, [
            'block' => $block,
            'blockController' => $adminController
        ]);

        // Render block content
        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        // TODO move to re-usable twig extension later
        $filter = new \Twig\TwigFilter('translate', function ($string) {
            return $string;
        });
        $frontView->addFilter($filter);


        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch();
        $scss = (string) null;

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath();

            $scssFile = $path . 'resources/public/css/styles-variables.less';

            if (file_exists($scssFile)) {
                $scss .= PHP_EOL . file_get_contents($scssFile);
            }
        }

        $view->set('view', $view);

        // Fetch page
        $page = $pageRepository->fetchById($block->getPageId());

        $frontView->set('page', $page);

        $blockHtml = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $blockHtml;

        $parser = new \Frootbox\View\HtmlParser($blockHtml, $container);
        $blockHtml = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'modal' => [
                'html' => $html
            ],
            'blocks' => [
                'uid' => $block->getUidRaw(),
                'html' => $blockHtml,
            ],
        ]);
    }


    /**
     *
     */
    public function ajaxModalList(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
    ): Response
    {
        // Fetch blocks
        $result = $blocksRepository->fetch([
            'where' => [
                'uid' => $get->get('uid')
            ],
            'order' => [ 'orderId DESC' ]
        ]);

        return new Response('html', 200, [
            'blocks' => $result,
        ]);
    }

    /**
     *
     */
    public function ajaxSort(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
    ): Response
    {

        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $blockId) {

            $block = $blocksRepository->fetchById($blockId);

            $block->setOrderId($orderId--);
            $block->save();
        }

        $html = '<div data-blocks data-uid="' . $get->get('uid') . '">';

        define('EDITING', true);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $get->get('uid'),
            'html' => $html
        ]);
    }

    /**
     *
     */
    public function ajaxUpdate (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): Response
    {
        list($uid, $property) = explode('----', $get->get('uid'));

        // Parse uid
        preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:(.*?)$#i', $uid, $match);
        $className = str_replace('-', '\\', $match[1]);

        // Fetch target object
        $model = new $className($db);
        $row = $model->fetchById($match[2]);

        // Extract property
        if (substr($property, 0, 7) == 'config.') {

            $configPath = substr($property, 7);

            $row->addConfig([
                $configPath => $post->get('value')
            ]);
        }
        else {
            $setter = 'set' . ucfirst($property);
            $propertyValue = $row->$setter($post->get('value'));
        }

        $row->save();

        return self::getResponse('json', 200, [
            'value' => $post->get('value'),
            'uid' => $uid,
            'property' => $property
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateConfig(
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view,
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($post->get('blockId'));

        parse_str(urldecode($post->get('variables')), $data);

        $block->unsetConfig('template.variables');

        $block->addConfig([
            'skipLanguages' => $data['skipLanguages'] ?? [],
            'template' => [
                'variables' => $data['variables'] ?? [],
            ],
        ]);

        $block->save();



        $html = $container->call([ $block, 'renderHtml' ]);

        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch();
        $scss = (string) null;

        foreach ($result as $extension) {

            $path = $extension->getExtensionController()->getPath();

            $scssFile = $path . 'resources/public/css/styles-variables.less';

            if (file_exists($scssFile)) {
                $scss .= PHP_EOL . file_get_contents($scssFile);
            }
        }

        $html = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $html;


        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $block->getUidRaw(),
            'blockId' => $block->getId(),
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateVariables(
        \Frootbox\Http\Post $post,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($post->get('blockId'));

        parse_str(urldecode($post->get('variables')), $data);

        $block->unsetConfig('template.variables');
        $block->addConfig([
            'template' => [
                'variables' => $data['variables'] ?? [],
            ],
        ]);
        $block->save();

        $html = '<div data-blocks data-uid="' . $block->getUidRaw() . '">';

        define('EDITING', true);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse']);

        return self::getResponse('json', 200, [
            'uid' => $block->getUidRaw(),
            'html' => $html,
        ]);
    }
}
