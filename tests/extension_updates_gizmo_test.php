<?php

// Run with: php tests/extension_updates_gizmo_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/admin/classes/AbstractGizmo.php';
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/admin/classes/View.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Admin/Gizmos/ExtensionUpdates/Gizmo.php';

class UpdateTestController extends \Frootbox\AbstractExtensionController
{
    public function __construct(private string $path, string $version)
    {
        $this->config = ['version' => $version];
    }

    public function getPath(): string
    {
        return $this->path;
    }
}

class UpdateTestExtension extends \Frootbox\Persistence\Extension
{
    public int $saveCount = 0;
    public \Frootbox\AbstractExtensionController $controller;

    public function getExtensionController(): \Frootbox\AbstractExtensionController
    {
        return $this->controller;
    }

    public function save(): \Frootbox\Db\Row
    {
        ++$this->saveCount;
        return $this;
    }
}

class UpdateTestRepository extends \Frootbox\Persistence\Repositories\Extensions
{
    public function __construct(private \Frootbox\Db\Result $rows) {}

    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        if (($params['where']['isactive'] ?? null) !== 1) {
            throw new RuntimeException('Only active extensions may be synchronized.');
        }
        return $this->rows;
    }
}

class UpdateTestResult extends \Frootbox\Db\Result
{
    public function __construct(array $rows, \Frootbox\Db\Db $db)
    {
        parent::__construct($rows, $db);
        $this->total = count($rows);
    }
}

class UpdateTestMigration
{
    public function getDescription(): string
    {
        return 'Test migration';
    }
}

class_alias(UpdateTestMigration::class, 'Frootbox\\Ext\\Test\\Updates\\Migrations\\Version000001');
$temporaryRoot = sys_get_temp_dir() . '/frootbox-extension-updates-' . bin2hex(random_bytes(8));
$migrationPath = $temporaryRoot . '/with-migrations/classes/Migrations/';
mkdir($migrationPath, 0777, true);
file_put_contents($migrationPath . 'Version000001.php', '<?php');
file_put_contents($migrationPath . 'README.md', 'Not a migration');
mkdir($temporaryRoot . '/empty/classes/Migrations/', 0777, true);

try {
    $db = (new ReflectionClass(\Frootbox\Db\Db::class))->newInstanceWithoutConstructor();
    $view = (new ReflectionClass(\Frootbox\Admin\View::class))->newInstanceWithoutConstructor();
    $config = new \Frootbox\Config\Config();
    $gizmo = new \Frootbox\Ext\Core\System\Admin\Gizmos\ExtensionUpdates\Gizmo();
    $cases = [
        'initial version without migrations' => ['missing', '0.0.0', '0.0.1', '0.0.1', 1, 0],
        'empty migration directory' => ['empty', '0.0.0', '0.0.1', '0.0.1', 1, 0],
        'pending migration' => ['with-migrations', '0.0.0', '0.0.1', '0.0.0', 0, 1],
        'release after last migration' => ['with-migrations', '0.0.1', '0.0.2', '0.0.2', 1, 0],
        'already current' => ['missing', '0.0.1', '0.0.1', '0.0.1', 0, 0],
        'never downgrade' => ['missing', '0.0.2', '0.0.1', '0.0.2', 0, 0],
    ];

    foreach ($cases as $label => [$directory, $installed, $available, $expected, $saves, $remaining]) {
        $extension = new UpdateTestExtension(['vendorId' => 'Test', 'extensionId' => 'Updates', 'version' => $installed]);
        $extension->controller = new UpdateTestController($temporaryRoot . '/' . $directory . '/', $available);
        $rows = new UpdateTestResult([$extension], $db);
        $gizmo->onBeforeRendering($view, $config, new UpdateTestRepository($rows));

        if ($extension->getVersion() !== $expected || $extension->saveCount !== $saves || $rows->getCount() !== $remaining) {
            throw new RuntimeException($label . ' failed');
        }

        // A subsequent render must not write the synchronized version again.
        $gizmo->onBeforeRendering($view, $config, new UpdateTestRepository(new UpdateTestResult([$extension], $db)));
        if ($extension->saveCount !== $saves) {
            throw new RuntimeException($label . ': repeated write');
        }
        echo $label . ": PASS\n";
    }
}
finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporaryRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temporaryRoot);
}
