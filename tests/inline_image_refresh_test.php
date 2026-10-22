<?php
// Run with: php tests/inline_image_refresh_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/admin/classes/Controller/Response.php';
require CORE_DIR . 'cms/extensions/Core/Editing/classes/Editables/AbstractController.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/EditableInterface.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Editables/Image/Editable.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Editables/Background/Editable.php';
require CORE_DIR . 'cms/extensions/Core/Images/classes/Editables/Image/Admin/Controller.php';

class InlineImageFile extends \Frootbox\Persistence\File {
    public function getUri(array $options = null): string { return '/image-' . $this->getId() . '.jpg'; }
    public function getUriThumbnail(array $options = null): string { return $this->getUri(); }
}
class InlineImageFiles extends \Frootbox\Persistence\Repositories\Files {
    public function __construct() {}
    public function fetchByUid($uid, array $options = null) {
        return in_array($uid, ['image', 'hero', 'hero-desktop'], true)
            ? new InlineImageFile(['id' => $uid === 'hero-desktop' ? 2 : 1, 'name' => 'Test']) : null;
    }
}
function checkInline(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$container = new \DI\Container();
$container->set(\Frootbox\Persistence\Repositories\Files::class, new InlineImageFiles());
$container->set(\Frootbox\Config\Config::class, new \Frootbox\Config\Config());
$controller = new \Frootbox\Ext\Core\Images\Editables\Image\Admin\Controller();
foreach ([
    ['image', '<picture data-editable data-inline-upload data-uid="image"><img width="600" height="400" src="/placeholder.jpg" alt=""></picture>'],
    ['background', '<div data-type="Images/Background" data-inline-upload data-uid="hero"></div>'],
] as [$type, $snippet]) {
    $_POST = ['type' => $type, 'snippet' => $snippet];
    $response = json_decode($controller->ajaxInlineRefresh(new \Frootbox\Http\Post(), $container)->getBody(), true);
    $html = $response['html'];
    checkInline(str_contains($html, 'data-inline-upload'), 'Opt-in marker lost');
    checkInline(str_contains($html, '/image-1.jpg'), 'Replacement not rendered');
    if ($type === 'background') {
        checkInline(str_contains($html, '/image-2.jpg') && str_contains($html, '<style>'), 'Desktop styles missing');
        checkInline(str_contains($html, 'data-focus-file-desktop="2"'), 'Desktop focus metadata missing');
    } else {
        checkInline(str_contains($html, 'data-default="/placeholder.jpg"'), 'Placeholder lost');
        checkInline(str_contains($html, 'width="600"') && str_contains($html, 'height="400"'), 'Dimensions lost');
    }
}
$_POST = ['type' => 'arbitrary', 'snippet' => ''];
try {
    $controller->ajaxInlineRefresh(new \Frootbox\Http\Post(), $container);
    throw new RuntimeException('Unknown renderer accepted');
} catch (\Frootbox\Exceptions\InputInvalid $expected) {}

$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(CORE_DIR));
foreach (['Image', 'Background'] as $type) {
    $source = $twig->render('cms/extensions/Core/Images/classes/Editables/' . $type . '/View.html.twig', [
        'settings' => ['serverpath' => '/', 'nonce' => 'test'], 'SID' => '',
    ]);
    checkInline(str_contains($source, 'window.frootboxInlineImages'), 'Shared uploader not included');
    checkInline(str_contains($source, 'nonce="test"'), 'Script nonce missing');
}
echo "Inline image refresh: image, background, desktop styles, metadata, renderer allowlist and Twig integration PASS\n";
