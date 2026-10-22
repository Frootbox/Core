<?php
// Run with: php tests/inline_image_upload_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('GLOBAL_LANGUAGE', 'de');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/extensions/Core/FileManager/classes/StaticPages/Editor/Page.php';
class InlineUploadResult extends \Frootbox\Db\Result {
    public bool $deleted = false;
    public function __construct() {}
    public function map(string $methodName): \Frootbox\Db\Result { $this->deleted = true; return $this; }
}
class InlineUploadFiles extends \Frootbox\Persistence\Repositories\Files {
    public bool $inserted = false;
    public function __construct(public InlineUploadResult $old) {}
    public function fetch(array $params = null): \Frootbox\Db\Result { return $this->old; }
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row {
        $this->inserted = true;
        throw new RuntimeException('Simulated storage failure');
    }
}
class InlineUploadFolders extends \Frootbox\Persistence\Repositories\Folders {
    public function __construct() {}
    public function fetchOne(array $params = null, array $options = null): ?\Frootbox\Db\Row {
        return new \Frootbox\Persistence\Folder(['id' => 1]);
    }
}
$path = tempnam(sys_get_temp_dir(), 'inline-upload-');
register_shutdown_function(static function () use ($path) { unlink($path); });
$page = new \Frootbox\Ext\Core\FileManager\StaticPages\Editor\Page();
$old = new InlineUploadResult();
$files = new InlineUploadFiles($old);
$folders = new InlineUploadFolders();
$config = new \Frootbox\Config\Config();
$_GET = ['uid' => 'test-image', 'inlineImage' => '1'];
$_FILES = ['file' => ['tmp_name' => $path, 'name' => 'image.png', 'type' => 'image/png', 'size' => 10, 'error' => 0]];
file_put_contents($path, 'Not an image');
try {
    $page->upload($folders, new \Frootbox\Http\Get(), $config, $files);
    throw new RuntimeException('Invalid image accepted');
} catch (\Frootbox\Exceptions\InputInvalid $expected) {}
if ($old->deleted || $files->inserted) throw new RuntimeException('Invalid image altered stored files');
file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aB1sAAAAASUVORK5CYII='));
try {
    $page->upload($folders, new \Frootbox\Http\Get(), $config, $files);
} catch (RuntimeException $expected) {
    if ($expected->getMessage() !== 'Simulated storage failure') throw $expected;
}
if (!$files->inserted || $old->deleted) throw new RuntimeException('Storage failure deleted the previous image');
// General uploads retain their previous behavior without inlineImage.
$_GET = ['uid' => 'test-image'];
try { $page->upload($folders, new \Frootbox\Http\Get(), $config, $files); } catch (RuntimeException $expected) {}
if (!$old->deleted) throw new RuntimeException('Legacy replacement behavior changed');
echo "Inline upload: content validation, preservation on storage exception, legacy behavior PASS\n";
