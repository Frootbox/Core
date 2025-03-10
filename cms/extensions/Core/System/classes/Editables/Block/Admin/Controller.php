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
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param Container $container
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Config\Config $config
     * @param \Frootbox\CloningMachine $cloningMachine
     * @param \Frootbox\Persistence\Repositories\Pages $pageRepository
     * @param \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
     * @param \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxCreate(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Db\Db $db,
    ): Response
    {
        // Validate required input
        $post->require([ 'block' ]);

        // Fetch page
        $page = $pageRepository->fetchById($get->get('pageId'));

        // Make space for new block
        $siblings = $blocksRepository->fetch([
            'where' => [
                'uid' => $get->get('uid'),
            ],
            'order' => [ 'orderId DESC', 'id ASC' ],
        ]);

        $orderId = $siblings->getCount() * 10 + 10;

        if (!empty($get->get('predecessor'))) {

            foreach ($siblings as $sibling) {

                $sibling->setOrderId($orderId);
                $sibling->save();

                if ($get->get('predecessor') == $sibling->getId()) {
                    $newOrderId = $orderId;
                }

                $orderId -= 10;
            }

            $orderId = $newOrderId--;
        }

        if ($post->get('block') != 'fromClipboard') {

            $data = explode('-', $post->get('block'));

            // Compose new block
            $block = new \Frootbox\Persistence\Content\Blocks\Block([
                'pageId' => $page->getId(),
                'uid' => $get->get('uid'),
                'vendorId' => $data[0],
                'extensionId' => $data[1],
                'blockId' => $data[2],
                'orderId' => $orderId,
                'config' => $config->get('Ext.' . $data[0] . '.' . $data[1] . '.Blocks.' . $data[2] . '.defaultConfig') ?? [],
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

                if (preg_match('#extends: ([a-z0-9]+)/([a-z0-9]+)/([a-z0-9]+)\s#i', $source, $match)) {

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

            $block->addConfig($block->getInitConfig());
            $block->save();

            if (method_exists($block, 'onInit')) {
                $container->call([ $block, 'onInit' ]);
            }
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
            $newBlock->setPageId($page->getId());
            $newBlock->setOrderId($orderId);

            // Check if custom class needs to be updated
            if (empty($block->getClassName())) {

                $extensionController = $block->getExtensionController();
                $classPath = $extensionController->getPath() . 'classes/Blocks/' . $block->getBlockId() . '/Block.php';

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
                        $newBlock->setClassName($className);
                    }
                }
            }

            $newBlock = $blocksRepository->persist($newBlock);

            // Clone contents
            $cloningMachine->cloneContentsForElement($newBlock, $block->getUidBase());

            // Clone elements contents
            if ($newBlock instanceof \Frootbox\Persistence\Interfaces\Cloneable) {

                $container->call([$newBlock, 'cloneContentFromAncestor'], [
                    'ancestor' => $block,
                ]);
            }

            $block = $newBlock;

            if (empty($post->get('keepClipboard'))) {
                unset($_SESSION['editmode']['editables']['block']['copy']);
            }
        }


        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);
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
        $view->set('page', $page);

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
     * @param \Frootbox\Http\Get $get
     * @param Container $container
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
     * @param \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxBlockDelete(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        // Delete block
        $block->delete();

        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';

        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);
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
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($get->get('blockId'));

        $block->visibilityPush();

        return self::getResponse('json', 200, [
            'blockId' => $block->getId(),
            'uid' => $block->getUidRaw(),
            'visibility' => $block->getVisibilityString(),
            'visibilityIcon' => $block->getVisibilityIcon(),
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
        $ignore = !empty($attributes['ignore']) ? explode(',', $attributes['ignore']) : [];

        $list = [];
        $loop = 0;
        $extLoop = 0;

        $categories = [
            'Template' => [
                'category' => 'Template',
                'title' => 'Template',
                'blocks' => [],
            ],
        ];

        $blockDataCache = [];

        // Fetch extensions
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $thumbnailOverridePath = null;
        $thumbnailOverrideSection = null;

        foreach ($result as $extension) {

            $extController = $extension->getExtensionController();

            if ($extController->getType() == 'Template') {

                $path = $extController->getPath() . 'resources/private/blockThumbnails/';

                if (file_exists($path)) {
                    $thumbnailOverridePath = $path;
                    $thumbnailOverrideSection = $extension->getVendorId() . '/' . $extension->getExtensionId();
                    break;
                }
            }
        }

        $addToDefault = [];

        foreach ($result as $extension) {

            $extController = $extension->getExtensionController();

            ++$extLoop;
            $loopKey = $extController->getType() == 'Template' ? $extLoop + 200 : $extLoop + 100;

            $dir = new \Frootbox\Filesystem\Directory($extController->getPath() . 'classes/Blocks/');

            $extList = [
                'extension' => $extension->getVendorId() . '/' . $extension->getExtensionId(),
                'blockCount' => 0,
                'blocks' => [
                    'Default' => [

                    ],
                ],
            ];

            foreach ($dir as $file) {

                $fileName = $file->getName();

                if ($fileName[0] == '_') {
                    continue;
                }

                if (!file_exists($dir->getPath() . $file . '/Block.html.twig')) {
                    continue;
                }

                $blockTemplate = new \Frootbox\View\Blocks\BlockTemplate($dir->getPath() . $file);

                $category = $blockTemplate->getConfig('category') ?? 'Unknown';

                if (!empty($blockTemplate->getConfig('extension'))) {

                    list($vendorId, $extensionId) = explode('/', $blockTemplate->getConfig('extension'));

                    $check = $extensionsRepository->fetchOne([
                        'where' => [
                            'vendorId' => $vendorId,
                            'extensionId' => $extensionId,
                        ],
                    ]);

                    if (empty($check)) {
                        continue;
                    }
                }

                if (!isset($categories[$category])) {
                    $categories[$category] = [
                        'category' => $category,
                        'title' => $category,
                        'blocks' => [],
                    ];
                }


                if (!empty($restricted)) {

                    $restrictions = $blockTemplate->getConfig('restricted');

                    if (empty($restrictions) or !in_array($restricted, $restrictions)) {
                        continue;
                    }
                }

                $restrictions = $blockTemplate->getConfig('restricted');

                if (!empty($restrictions)) {

                    if (empty($restricted)) {
                        continue;
                    }

                    if (empty($restrictions) or !in_array($restricted, $restrictions)) {
                        continue;
                    }
                }

                /*
                if (!empty($ignore)) {

                    $restrictions = $blockTemplate->getConfig('restricted');

                    if (!empty($restrictions)) {

                        foreach ($ignore as $xignore) {
                            foreach ($restrictions as $restriction) {
                                if ($xignore == $restriction) {
                                    continue 3;
                                }
                            }
                        }
                    }
                }
                */

                $source = file_get_contents($dir->getPath() . $file . '/Block.html.twig') . "\n";

                if (preg_match('#override:#', $source)) {
                    continue;
                }

                $section = 'Default';

                if (preg_match('#section: (.*?)\n#', $source, $match)) {
                    $section =  $match[1];
                }

                $blockData = [
                    'blockId' => (string) $file,
                    'vendorId' => $extension->getVendorId(),
                    'extensionId' => $extension->getExtensionId(),
                    'template' => $blockTemplate,
                ];

                $categories[$category]['blocks'][$blockTemplate->getTitle() . $blockTemplate->getSubTitle() . ++$loop] = $blockData;
                $extList['blocks'][$section][$blockTemplate->getTitle() . $blockTemplate->getSubTitle() . ++$loop] = $blockData;
                ++$extList['blockCount'];

                $blockDataCache[$blockData['vendorId']][$blockData['extensionId']][$blockData['blockId']] = $blockData;

                if ($thumbnailOverridePath) {
                    $blockTemplate->captureThumbnail($thumbnailOverridePath . $extension->getVendorId() . '/' . $extension->getExtensionId() . '/' . $file . '/');

                    if ($blockTemplate->getOverrideThumbnail()) {
                        $addToDefault[] = $blockData;
                    }
                }
            }

            if (!empty($extList['blockCount'])) {

                foreach ($extList['blocks'] as $section => $blocks) {
                    ksort($blocks);
                    $extList['blocks'][$section] = $blocks;
                }

                $list[$loopKey] = $extList;
            }
        }

        if ($thumbnailOverrideSection and !empty($addToDefault)) {

            foreach ($list as $loopId => $sectionData) {

                if ($sectionData['extension'] == $thumbnailOverrideSection) {

                    foreach ($addToDefault as $block) {
                        $title = $block['template']->getTitle();
                        $checkTitle = $title;
                        $loop = 0;

                        while (isset($sectionData['blocks']['Default'][$checkTitle])) {
                            $checkTitle = $title . ++$loop;
                        }

                        $list[$loopId]['blocks']['Default'][$checkTitle] = $block;
                    }
                }
            }
        }

        function isListedInTemplate(\Frootbox\Persistence\Content\Blocks\Block $block, array $list): bool
        {
            if (empty($list)) {
                return false;
            }

            foreach ($list as $index => $blockData) {

                if ($block->getBlockId() == $blockData['blockId'] and $block->getVendorId() == $blockData['vendorId'] and $block->getExtensionId() == $blockData['extensionId']) {
                    return true;
                }
            }

            return false;
        }

        $templateBlocks = $categories['Template']['blocks'] ?? [];

        foreach ($blocksRepository->fetch() as $block) {

            if (!isListedInTemplate($block, $templateBlocks)) {

                if (empty($blockDataCache[$block->getVendorId()][$block->getExtensionId()][$block->getBlockId()])) {
                    continue;
                }

                $blockData = $blockDataCache[$block->getVendorId()][$block->getExtensionId()][$block->getBlockId()];

                $blockTemplate = $blockData['template'];
                $keyTitle = $blockTemplate->getTitle() . $blockTemplate->getSubTitle() . rand(1000, 9999);

                $templateBlocks[$keyTitle] = $blockData;
            }
        }

        $categories['Template']['blocks'] = $templateBlocks;

        // Sort blocks
        foreach ($categories as $category => $categoryData) {

            ksort($categoryData['blocks']);
            $categories[$category]['blocks'] = $categoryData['blocks'];
        }

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

        foreach ($categories as $index => $categoryData) {

            if (empty($categoryData['blocks'])) {
                unset($categories[$index]);
            }
        }

        return self::getResponse('html', 200, [
            'uid' => $get->get('uid'),
            'blocksList' => $list,
            'copied' => $copiedTemplate ?? null,
            'categories' => $categories,
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
    ): Response
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
        \Frootbox\View\Blocks\PreviewRenderer $previewRenderer,
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

            $bodyData = $response->getBodyData();

            if (!empty($bodyData['adminAction'])) {

                // Call admin action
                $adminController = $block->getAdminController();

                $action = $bodyData['adminAction'];
                $xResponse = $container->call([ $adminController, $action . 'Action' ]);

                $xViewFile = $adminController->getPath() . 'resources/private/views/' . ucfirst($action) . '.html.twig';

                if (!empty($xResponse->getBodyData())) {

                    foreach ($xResponse->getBodyData() as $key => $value) {
                        $view->set($key, $value);
                    }
                }

                $adminHtml = $view->render($xViewFile, null, [
                    'block' => $block,
                    'blockController' => $adminController,
                    'controller' => $this,
                ]);

                $bodyData['admin']['html'] = $adminHtml;

                $response->setBodyData($bodyData);
            }

            $bodyData = $response->getBodyData();

            // Render blocks html
            $blockHtml = $previewRenderer->render($block);

            $bodyData['blocks'] = [
                'uid' => $block->getUidRaw(),
                'html' => $blockHtml,
            ];

            $response->setBodyData($bodyData);

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

        // Render blocks html
        $blockHtml = $previewRenderer->render($block);

        /*

        // Render block content
        $blockHtml = '<div data-blocks data-uid="' . $block->getUidRaw() . '"></div>';


        define('EDITING', true);

        // Inject scss variables
        $result = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);
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
        if (!empty($block->getPageId())) {
            $page = $pageRepository->fetchById($block->getPageId());
            $frontView->set('page', $page);
        }
        else {

            $page = $pageRepository->fetchOne([
                'order' => [ 'lft ASC' ],
            ]);
            $frontView->set('page', $page);
        }

        $blockHtml = '<style type="text/less">' . $scss . PHP_EOL . '</style>' . PHP_EOL . $blockHtml;

        $parser = new \Frootbox\View\HtmlParser($blockHtml, $container);
        $blockHtml = $container->call([ $parser, 'parse']);

        */

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
        \Frootbox\Persistence\Content\Repositories\Blocks $blockRepository,
    ): Response
    {
        // Fetch blocks
        $result = $blockRepository->fetch([
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
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Persistence\Content\Repositories\Blocks $blockRepository
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxOverrideStylesheet(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\Blocks $blockRepository,
    ): Response
    {
        // Fetch block
        $block = $blockRepository->fetchById($get->get('blockId'));

        $scss = 'html body .EditableBlock.' . $block->getExtensionId() . '.' . $block->getBlockId() . " {\n\n}";

        return new Response('json', 200, [
            'scss' => $scss,
        ]);
    }

    public function ajaxOverrideThumbnail(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Blocks $blockRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionController,
    ): Response
    {
        // Fetch block
        $block = $blockRepository->fetchById($get->get('blockId'));

        // Fetch extensions
        $extensions = $extensionController->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        foreach ($extensions as $extension) {

            if ($extension->getExtensionController()->getType() == 'Template') {

                $path = $extension->getExtensionController()->getPath() . 'resources/private/blockThumbnails/' . $block->getVendorId() . '/' . $block->getExtensionId() . '/' . $block->getBlockId();

                if (!file_exists($path)) {
                    $old = umask(0);
                    mkdir($path, 0755, true);
                    umask($old);
                }

                break;
            }
        }

        return new Response('json', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxSort(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
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
    public function ajaxUpdate(
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
     * @param \Frootbox\Http\Post $post
     * @param Container $container
     * @param \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository
     * @param \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxUpdateConfig(
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\View\Blocks\PreviewRenderer $previewRenderer,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
    ): Response
    {
        // Fetch block
        $block = $blocksRepository->fetchById($post->get('blockId'));

        parse_str(urldecode($post->get('variables')), $data);

        $block->unsetConfig('template.variables');

        $block->unsetConfig('skipLanguages');
        $block->addConfig([
            'noPrint' => !empty($data['noPrint']),
            'skipLanguages' => $data['skipLanguages'] ?? [],
            'template' => [
                'variables' => $data['variables'] ?? [],
            ],
            'margin' => [
                'top' => $data['marginTop'] ?? null,
                'left' => $data['marginLeft'] ?? null,
                'bottom' => $data['marginBottom'] ?? null,
                'right' => $data['marginRight'] ?? null,
            ],
            'css' => [
                'className' => $data['cssClass'] ?? null,
            ],
        ]);

        $block->save();


        // Render blocks html
        $blockHtml = $previewRenderer->render($block);


        /*

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
        */

        return self::getResponse('json', 200, [
            'uid' => $block->getUidRaw(),
            'blockId' => $block->getId(),
            'html' => $blockHtml,
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
