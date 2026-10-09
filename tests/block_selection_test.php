<?php

// Run with: php tests/block_selection_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/extensions/Core/Editing/classes/Editables/AbstractController.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Block/Admin/Controller.php';

class SelectionExtensionController extends \Frootbox\AbstractExtensionController
{
    public function __construct(private string $path, string $type)
    {
        $this->type = $type;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}

class SelectionExtension extends \Frootbox\Persistence\Extension
{
    public static array $controllers = [];

    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        return self::$controllers[$this->getExtensionId()];
    }
}

class SelectionBlock extends \Frootbox\Persistence\Content\Blocks\Block
{
    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        return SelectionExtension::$controllers[$this->getExtensionId()];
    }
}

class SelectionBlocks extends \Frootbox\Persistence\Content\Repositories\Blocks
{
    public int $fetchCount = 0;

    public function __construct(public \Frootbox\Db\Result $rows) {}

    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        ++$this->fetchCount;
        return $this->rows;
    }
}

class SelectionExtensions extends \Frootbox\Persistence\Repositories\Extensions
{
    public function __construct(private \Frootbox\Db\Result $rows) {}

    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        if (($params['where']['isactive'] ?? null) !== 1) {
            throw new RuntimeException('Selection must only enumerate active extensions.');
        }
        return $this->rows;
    }
}

$temporaryRoot = sys_get_temp_dir() . '/frootbox-block-selection-' . bin2hex(random_bytes(8));
$fixtures = ['Theme' => ['Hero'], 'Library' => ['Used', 'Extra', 'Hidden'], 'Other' => ['Extra']];
$db = (new ReflectionClass(\Frootbox\Db\Db::class))->newInstanceWithoutConstructor();
$extensions = [];

try {
    foreach ($fixtures as $extensionId => $blockIds) {
        $path = $temporaryRoot . '/' . $extensionId . '/';
        SelectionExtension::$controllers[$extensionId] = new SelectionExtensionController($path, $extensionId === 'Theme' ? 'Template' : 'Generic');
        $extensions[] = new SelectionExtension(['vendorId' => 'Test', 'extensionId' => $extensionId]);
        foreach ($blockIds as $blockId) {
            $directory = $path . 'classes/Blocks/' . $blockId;
            mkdir($directory, 0777, true);
            file_put_contents($directory . '/Block.html.twig', "{# config\ntitle: $blockId\ncategory: Content\n/config #}\n");
        }
    }

    $used = new SelectionBlock(['vendorId' => 'Test', 'extensionId' => 'Library', 'blockId' => 'Used']);
    $extensionRepository = new SelectionExtensions(new \Frootbox\Db\Result($extensions, $db));
    $controller = new \Frootbox\Ext\Core\System\Editables\Block\Admin\Controller();
    $all = ['Test/Theme/Hero', 'Test/Library/Used', 'Test/Library/Extra', 'Test/Library/Hidden', 'Test/Other/Extra'];
    $base = ['Test/Theme/Hero', 'Test/Library/Used'];
    $cases = [
        'default' => [[], 'Admin', [$used], $all],
        'empty lists' => [['AllowedCategories' => [], 'AllowedBlocks' => []], 'Admin', [$used], $all],
        'only used' => [['OnlyUsedBlocks' => true], 'Admin', [$used], $base],
        'no usage' => [['OnlyUsedBlocks' => true], 'Admin', [], ['Test/Theme/Hero']],
        'explicit block exception' => [['OnlyUsedBlocks' => true, 'AllowedBlocks' => ['Test/Library/Extra']], 'Admin', [$used], [...$base, 'Test/Library/Extra']],
        'extension exception' => [['OnlyUsedBlocks' => true, 'AllowedCategories' => ['Test/Other']], 'Admin', [$used], [...$base, 'Test/Other/Extra']],
        'block allowlist' => [['AllowedBlocks' => ['Test/Library/Extra']], 'Admin', [], ['Test/Theme/Hero', 'Test/Library/Extra']],
        'combined allowlists' => [['AllowedCategories' => ['Test/Other'], 'AllowedBlocks' => ['Test/Library/Extra']], 'Admin', [], ['Test/Theme/Hero', 'Test/Other/Extra', 'Test/Library/Extra']],
        'legacy used list' => [['AllowedCategories' => ['Test/Other']], 'Admin', [$used], [...$base, 'Test/Other/Extra']],
        'unknown block' => [['AllowedBlocks' => ['Test/Library/Missing']], 'Admin', [], ['Test/Theme/Hero']],
        'superadmin' => [['OnlyUsedBlocks' => true, 'AllowedBlocks' => ['Test/Library/Extra']], 'SuperAdmin', [], $all],
        'duplicate usage' => [['OnlyUsedBlocks' => true], 'Admin', [$used, clone $used], $base],
    ];

    foreach ($cases as $label => [$settings, $role, $rows, $expected]) {
        $_SESSION = ['user' => ['type' => $role]];
        $_GET = [];
        $config = new \Frootbox\Config\Config();
        $config->append(['Ext' => ['Core' => ['System' => ['Editables' => ['Block' => $settings]]]]]);
        $blockRepository = new SelectionBlocks(new \Frootbox\Db\Result($rows, $db));
        $body = $controller->ajaxModalCompose(new \Frootbox\Http\Get(), $config, $blockRepository, $extensionRepository)->getBodyData();
        $actual = [];
        foreach ($body['categories'] as $category) {
            foreach ($category['blocks'] as $block) {
                $actual[] = $block['vendorId'] . '/' . $block['extensionId'] . '/' . $block['blockId'];
            }
        }
        $actual = array_values(array_unique($actual));
        sort($actual);
        sort($expected);
        if ($actual !== $expected || $blockRepository->fetchCount !== 1) {
            throw new RuntimeException($label . ': unexpected selection ' . json_encode($actual));
        }
        foreach ($body['blocksList'] as $extension) {
            foreach ($extension['blocks'] as $blocks) {
                foreach ($blocks as $block) {
                    $id = $block['vendorId'] . '/' . $block['extensionId'] . '/' . $block['blockId'];
                    if (!in_array($id, $expected, true)) {
                        throw new RuntimeException($label . ': disallowed block in extension list: ' . $id);
                    }
                }
            }
        }
    }
    echo 'block_selection: ' . count($cases) . " cases PASS\n";
}
finally {
    if (is_dir($temporaryRoot)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporaryRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($temporaryRoot);
    }
}
