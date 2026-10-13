<?php

// Run with: php tests/background_focus_point_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
define('FILES_DIR', sys_get_temp_dir() . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/EditableInterface.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Editables/Background/Editable.php';

class BackgroundFocusFile extends \Frootbox\Persistence\File
{
    public array $thumbnailOptions = [];

    public function getUri(array $options = null): string
    {
        $this->thumbnailOptions = $options;
        return '/test-' . $this->getId() . '.jpg';
    }
}

class BackgroundFocusFiles extends \Frootbox\Persistence\Repositories\Files
{
    public function __construct(public array $images) {}

    public function fetchByUid($uid, array $options = null)
    {
        return $this->images[$uid] ?? null;
    }

    public function fetchById($rowId): \Frootbox\Db\Row
    {
        foreach ($this->images as $file) {
            if ($file->getId() == $rowId) {
                return $file;
            }
        }
        throw new \Frootbox\Exceptions\NotFound();
    }
}

function checkBackgroundFocus(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$fixture = tempnam(FILES_DIR, 'background-focus-');
register_shutdown_function(static function () use ($fixture): void { unlink($fixture); });
$bitmap = imagecreatetruecolor(200, 100);
imagepng($bitmap, $fixture);
imagedestroy($bitmap);
$mobile = new BackgroundFocusFile(['id' => 1, 'name' => 'Mobile', 'path' => basename($fixture)]);
$desktop = new BackgroundFocusFile(['id' => 2, 'name' => 'Desktop', 'path' => basename($fixture)]);
$mobile->addConfig(['focusPoint' => ['x' => 0, 'y' => 0]]);
$desktop->addConfig(['focusPoint' => ['x' => 199, 'y' => 99]]);
$files = new BackgroundFocusFiles(['hero' => $mobile, 'hero-desktop' => $desktop]);
$editable = new \Frootbox\Ext\Core\Images\Editables\Background\Editable();
$config = new \Frootbox\Config\Config();

$render = static function (string $attributes) use ($editable, $files, $config): string {
    return $editable->parse('<html><head></head><body><div data-type="Images/Background" ' . $attributes . '></div></body></html>', $files, $config);
};
$html = $render('data-uid="hero" data-width="600" data-height="400"');
checkBackgroundFocus(str_contains($html, 'data-focus-file="1"') && str_contains($html, 'data-focus-file-desktop="2"'), 'Both displayed files must be selectable.');
checkBackgroundFocus(str_contains($html, 'background-position: 0% 0%;'), 'Zero coordinates must position the mobile background.');
checkBackgroundFocus(str_contains($html, 'background-position: 100% 100% !important;'), 'Desktop focus must override the inline mobile position.');
checkBackgroundFocus($mobile->thumbnailOptions['crop'] === 'none' && $desktop->thumbnailOptions['crop'] === 'none', 'Focused backgrounds must retain the complete image for CSS cover.');

$html = $render('data-uid="missing" data-fallback-uid="hero"');
checkBackgroundFocus(str_contains($html, 'data-focus-file="1"'), 'Fallback image must be selected for editing.');
$html = $render('data-file="2"');
checkBackgroundFocus(str_contains($html, 'data-focus-file="2"'), 'Direct file references must keep their identity.');

$mobile->addConfig(['rotation' => 90, 'focusPoint' => ['x' => 99, 'y' => 199]]);
$html = $render('data-uid="hero"');
checkBackgroundFocus(str_contains($html, 'background-position: 100% 100%;'), 'Focus positioning must use rotated dimensions.');

$mobile->addConfig(['focusPoint' => null]);
unset($files->images['hero-desktop']);
$html = $render('data-uid="hero" data-focus-file-desktop="2"');
checkBackgroundFocus(str_contains($html, 'background-position: center;') && !isset($mobile->thumbnailOptions['crop']), 'Reset must restore the original centered rendering.');
checkBackgroundFocus(str_contains($html, 'data-focus-file-desktop=""'), 'Removed desktop images must not leave a stale focus target.');

$mobile->addConfig(['backgroundColor' => '#ffffff']);
$html = $render('data-uid="hero" data-focus-file="1"');
checkBackgroundFocus(str_contains($html, 'data-focus-file=""'), 'Color-only backgrounds must not offer image focus editing.');
$html = $render('data-uid="missing"');
checkBackgroundFocus(str_contains($html, 'data-focus-file=""'), 'Empty backgrounds must not offer image focus editing.');

echo "Background focus: mobile/desktop, fallback, rotation, cover positioning, reset and empty backgrounds PASS\n";
