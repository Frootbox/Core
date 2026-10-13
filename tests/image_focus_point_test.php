<?php

// Run with: php tests/image_focus_point_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('FILES_DIR', sys_get_temp_dir() . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/admin/classes/View.php';
require CORE_DIR . 'cms/extensions/Core/Editing/classes/Editables/AbstractController.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Editables/Image/Admin/Controller.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Persistence/Image.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Persistence/Repositories/Images.php';

class FocusPointImage extends \Frootbox\Ext\Core\Images\Persistence\Image
{
    public int $saves = 0;
    public function save(): \Frootbox\Db\Row
    {
        ++$this->saves;
        return $this;
    }
}

class FocusPointImages extends \Frootbox\Ext\Core\Images\Persistence\Repositories\Images
{
    public array $lookups = [];

    public function __construct(public FocusPointImage $image) {}

    public function fetchById($rowId): \Frootbox\Db\Row
    {
        if ($rowId !== '42') {
            throw new RuntimeException('Unexpected file ID.');
        }
        return $this->image;
    }

    public function fetchByUid($uid, array $options = null)
    {
        $this->lookups[] = [$uid, $options];
        return $uid === 'existing' ? $this->image : null;
    }
}

function checkFocusPoint(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$controller = new \Frootbox\Ext\Core\Images\Editables\Image\Admin\Controller();
$config = new \Frootbox\Config\Config();
$fixture = tempnam(FILES_DIR, 'focus-point-');
$bitmap = imagecreatetruecolor(800, 600);
imagepng($bitmap, $fixture);
imagedestroy($bitmap);
register_shutdown_function(static function () use ($fixture): void { unlink($fixture); });
$image = new FocusPointImage([ 'id' => 42, 'path' => basename($fixture) ]);
$image->addConfig([ 'caption' => 'Keep caption', 'width' => 300, 'focusPoint' => ['x' => 123, 'y' => 456] ]);
$files = new FocusPointImages($image);
$_GET = [ 'fileId' => '42' ];
$get = new \Frootbox\Http\Get();
$editView = new \Frootbox\Admin\View();
$editGet = (new \Frootbox\Http\Get())->set('uid', 'existing');
$controller->ajaxModalEdit($editGet, $editView, $files, $config);
checkFocusPoint($editView->get('dimensions') === ['width' => 800, 'height' => 600], 'Image editor must receive PNG dimensions.');

foreach ([[0, 0], [799, 599], [400, 300]] as [$x, $y]) {
    $_POST = [ 'position' => ['x' => (string) $x, 'y' => (string) $y] ];
    $response = $controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
    checkFocusPoint($image->getConfig('focusPoint') === ['x' => $x, 'y' => $y], 'Valid coordinates must be saved, including zero.');
    checkFocusPoint($response->getBodyData()['success'] === true, 'Missing success response.');
}

$invalidPositions = [null, 'invalid', [], ['x' => 1], ['x' => '', 'y' => ''], ['x' => -1, 'y' => 0], ['x' => 800, 'y' => 0], ['x' => 0, 'y' => 600], ['x' => '1.5', 'y' => 0], ['x' => [], 'y' => 0]];
foreach ($invalidPositions as $position) {
    $_POST = [ 'position' => $position ];
    $saves = $image->saves;
    try {
        $controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
        throw new RuntimeException('Invalid coordinates were accepted.');
    }
    catch (\Frootbox\Exceptions\RuntimeError $exception) {
        checkFocusPoint($image->saves === $saves, 'Invalid coordinates must not save.');
        checkFocusPoint($image->getConfig('focusPoint') === ['x' => 400, 'y' => 300], 'Invalid input changed the focus point.');
    }
}

$_POST = [ 'reset' => '1' ];
$controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
checkFocusPoint($image->getConfig('focusPoint') === null, 'Reset must remove the focus point.');
checkFocusPoint($image->getConfig('caption') === 'Keep caption' && $image->getConfig('width') === 300, 'Image metadata must remain unchanged.');

$image->setPath(basename($fixture) . '.missing');
$controller->ajaxModalEdit($editGet, $editView, $files, $config);
checkFocusPoint($editView->get('dimensions') === [], 'Missing images must not display dimensions.');
try {
    $controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
    throw new RuntimeException('Images without dimensions must be rejected.');
}
catch (\Frootbox\Exceptions\RuntimeError $exception) {}

foreach ([['existing', 'unused'], ['missing', 'existing'], ['missing', null]] as [$uid, $fallback]) {
    $_GET = [ 'uid' => $uid, 'fallbackUid' => $fallback ];
    $files->lookups = [];
    $response = $controller->ajaxModalFocusPoint(new \Frootbox\Http\Get(), $files, $config);
    checkFocusPoint($response->getBodyData()['file'] === ($fallback === null ? null : $image), 'Wrong image selected.');
    checkFocusPoint(count($files->lookups) === ($uid === 'missing' && $fallback ? 2 : 1), 'Unexpected fallback lookup.');
    foreach ($files->lookups as [$lookupUid, $options]) {
        checkFocusPoint($options === ['fallbackLanguageDefault' => true, 'order' => 'orderId DESC'], 'Lookup must match frontend rendering.');
    }
}

echo "Image focus point: persistence, validation, reset and fallback PASS\n";

// Optional integration coverage with a real HEIC encoder/decoder:
// IMAGEMAGICK_BINARY=/path/to/magick php tests/image_focus_point_test.php
if ($binary = getenv('IMAGEMAGICK_BINARY')) {
    define('PUBLIC_DIR', '/files/');
    $config->append(['thumbnails' => ['imagemagick' => ['path' => escapeshellarg($binary)]]]);
    $heicPath = $fixture . '.heic';
    $generatedFiles = [$heicPath];
    register_shutdown_function(static function () use (&$generatedFiles): void {
        foreach ($generatedFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    });

    $bitmap = imagecreatetruecolor(800, 400);
    imagefilledrectangle($bitmap, 0, 0, 399, 399, imagecolorallocate($bitmap, 255, 0, 0));
    imagefilledrectangle($bitmap, 400, 0, 799, 399, imagecolorallocate($bitmap, 0, 0, 255));
    imagepng($bitmap, $fixture);
    imagedestroy($bitmap);

    $process = proc_open([$binary, $fixture, $heicPath], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    checkFocusPoint(proc_close($process) === 0 && is_file($heicPath), 'HEIC fixture generation failed: ' . $output . $error);
    $image->setPath(basename($heicPath));
    $image->setType('image/heic');
    $controller->ajaxModalEdit($editGet, $editView, $files, $config);
    checkFocusPoint($editView->get('dimensions') === ['width' => 800, 'height' => 400], 'Image editor must receive HEIC dimensions.');

    $modalGet = (new \Frootbox\Http\Get())->set('uid', 'existing');
    $data = $controller->ajaxModalFocusPoint($modalGet, $files, $config)->getBodyData();
    checkFocusPoint($data['dimensions'] === ['width' => 800, 'height' => 400], 'HEIC dimensions incorrect.');
    $files->lookups = [];
    $directData = $controller->ajaxModalFocusPoint((new \Frootbox\Http\Get())->set('fileId', '42'), $files, $config)->getBodyData();
    checkFocusPoint($directData['file'] === $image && $directData['preview'] === $data['preview'] && $files->lookups === [], 'Background focus must open the exact file without resolving the UID again.');
    checkFocusPoint(!empty($data['preview']), 'HEIC preview missing.');
    $previewPath = FILES_DIR . substr($data['preview'], strlen(PUBLIC_DIR));
    $generatedFiles[] = $previewPath;
    $previewDimensions = getimagesize($previewPath);
    checkFocusPoint($previewDimensions['mime'] === 'image/jpeg', 'Preview must be browser-compatible JPEG.');
    checkFocusPoint($previewDimensions[0] === 2 * $previewDimensions[1], 'Preview must retain the full uncropped image.');

    $_POST = ['position' => ['x' => '799', 'y' => '200']];
    $controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
    checkFocusPoint($image->getConfig('focusPoint') === ['x' => 799, 'y' => 200], 'HEIC focus was not saved.');

    foreach ([0 => 'red', 799 => 'blue'] as $focusX => $expectedColor) {
        $thumbnail = new \Frootbox\Thumbnail([
            'path' => basename($heicPath), 'type' => 'png', 'width' => 100, 'height' => 100,
            'focusPoint' => ['x' => $focusX, 'y' => 200],
        ], $config->get('thumbnails'));
        $generatedFiles[] = $thumbnail->getCacheFilePath();
        $thumbnail->render();
        $crop = imagecreatefrompng($thumbnail->getCacheFile());
        $pixel = imagecolorsforindex($crop, imagecolorat($crop, 50, 50));
        imagedestroy($crop);
        checkFocusPoint($pixel[$expectedColor] > 200 && $pixel[$expectedColor === 'red' ? 'blue' : 'red'] < 50, 'HEIC crop ignored the focus point.');
    }

    $image->addConfig(['rotation' => 90]);
    $controller->ajaxModalEdit($editGet, $editView, $files, $config);
    checkFocusPoint($editView->get('dimensions') === ['width' => 400, 'height' => 800], 'Image editor must respect HEIC rotation.');
    $data = $controller->ajaxModalFocusPoint($modalGet, $files, $config)->getBodyData();
    checkFocusPoint($data['dimensions'] === ['width' => 400, 'height' => 800], 'Rotated HEIC dimensions incorrect.');
    $previewPath = FILES_DIR . substr($data['preview'], strlen(PUBLIC_DIR));
    $generatedFiles[] = $previewPath;
    $previewDimensions = getimagesize($previewPath);
    checkFocusPoint($previewDimensions[1] === 2 * $previewDimensions[0], 'Rotated preview orientation incorrect.');
    $_POST = ['position' => ['x' => '399', 'y' => '799']];
    $controller->ajaxUpdateFocusPoint($get, new \Frootbox\Http\Post(), $files, $config);
    checkFocusPoint($image->getConfig('focusPoint') === ['x' => 399, 'y' => 799], 'Rotated coordinates rejected.');

    echo "HEIC: actual file dimensions, JPEG preview, saved focus, left/right crops and rotation PASS\n";
}
