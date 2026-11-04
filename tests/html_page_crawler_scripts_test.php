<?php

// Run: php tests/html_page_crawler_scripts_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');

use Frootbox\View\HtmlPageCrawler;
use Frootbox\View\ScriptContents;

function checkScripts(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$scripts = [
    'const title = "Büro – 日本語 😀";',
    "const value = 'ä &amp; &lt; &#252; &uuml;';\r\nconst x = 1 < 2 && 3 > 2;",
    'const markup = "<div><p>ü</p></div><scripture>test</scripture>";',
    '{"title":"Büro","description":"<p>für dich &amp; mich</p>","literal":"&uuml;"}',
    '<!-- const template = "<script>double escaped</script>"; //-->',
    '',
];

foreach ($scripts as $index => $script) {
    $tag = '<script data-case="' . $index . '" nonce="nonce-value" type="application/ld+json" data-label="a > b">' . $script . '</script>';
    foreach ([$tag, '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $tag . '</body></html>'] as $source) {
        for ($pass = 0; $pass < 3; ++$pass) {
            $crawler = HtmlPageCrawler::create($source);
            $node = $crawler->filter('script');
            checkScripts($node instanceof HtmlPageCrawler, 'Selections must retain script protection');
            checkScripts($node->count() === 1 && $node->text(null, false) === $script, "Parse changed script $index");
            checkScripts($node->getInnerHtml() === $script, "Inner HTML changed script $index");
            checkScripts(str_contains($node->outerHtml(), '>' . $script . '</script>'), "Outer HTML changed script $index");
            $node->setAttribute('data-edited', 'yes');
            $original = $node->getNode(0)->firstChild;
            $source = $crawler->saveHTML();
            checkScripts(str_contains($source, '>' . $script . '</script>'), "Serialization changed script $index");
            checkScripts($node->getAttribute('nonce') === 'nonce-value' && $node->getAttribute('data-edited') === 'yes', 'Script attributes must remain editable');
            checkScripts($node->getNode(0)->firstChild === $original, 'Serialization must restore the original DOM nodes');
            checkScripts(!str_contains($source, 'FROOTBOX_SCRIPT_'), 'Temporary placeholders must not escape');
            checkScripts((string) $node === $node->saveHTML(), 'String conversion must preserve scripts');
        }
    }
}
echo "Script bodies: fragments, documents, Unicode, entities, HTML strings, legacy comments and repeated serialization PASS\n";

// Script-looking strings in other HTML contexts must not be protected as scripts.
$source = '<!-- <script>comment</script> --><textarea><script>textarea</script></textarea>'
    . '<style>p::after { content: "<script>style</script>"; }</style>'
    . '<div data-label="<script>attribute</script>">normal</div>'
    . '<script data-key="a > b">const value = "ü";</script>';
$guard = new ScriptContents();
$protected = $guard->protect($source);
checkScripts(substr_count($protected, 'FROOTBOX_SCRIPT_') === 1, 'Only actual script bodies may be replaced');
checkScripts($guard->restore($protected) === $source, 'Protection must be reversible');
$crawler = HtmlPageCrawler::create($source);
checkScripts($crawler->filter('script[data-key]')->count() === 1, 'Real script must remain selectable');
checkScripts(str_contains($crawler->saveHTML(), 'const value = "ü";'), 'Real script must remain intact');

foreach ([
    '<SCRIPT TYPE=module>const x = "ü";</SCRIPT >',
    '<script type="application/json">{"x":"ö"}</script>',
    '<script>const x = "ü";',
    '<script src="FILE:/tmp/demo.js">const fallback = "ä";</script>',
] as $source) {
    $guard = new ScriptContents();
    checkScripts($guard->restore($guard->protect($source)) === $source, 'Case, type, external src and EOF protection');
}
echo "Script detection: comments, attributes, raw-text elements, case, type and EOF PASS\n";

$script = '<script nonce="test">const x = "Büro"; const html = "<p>ü</p>";</script>';
foreach (['setInnerHtml', 'replaceWith', 'append', 'prepend', 'before', 'after'] as $method) {
    $crawler = HtmlPageCrawler::create('<main><div class="target">Old</div></main>');
    $crawler->filter('.target')->$method($script);
    $html = $crawler->saveHTML();
    checkScripts(str_contains($html, $script), "$method must preserve inserted script content");
}

foreach (['appendTo', 'prependTo', 'insertBefore', 'insertAfter', 'replaceAll'] as $method) {
    $crawler = HtmlPageCrawler::create('<main><div class="target">Old</div></main>');
    $inserted = HtmlPageCrawler::create($script)->$method($crawler->filter('.target'));
    checkScripts($inserted instanceof HtmlPageCrawler && $inserted->saveHTML() === $script, "$method must retain protected selections");
    checkScripts(str_contains($crawler->saveHTML(), $script), "$method must preserve moved script content");
}

foreach (['wrap', 'wrapAll', 'wrapInner'] as $method) {
    $crawler = HtmlPageCrawler::create('<main><div class="target">Old</div></main>');
    $crawler->filter('.target')->$method('<section><div></div>' . $script . '</section>');
    checkScripts(str_contains($crawler->saveHTML(), $script), "$method must preserve script content in wrappers");
}

$crawler = HtmlPageCrawler::create('<div><script src="FILE:/one.js"></script>' . $script . '</div>');
$crawler->filter('script[src]')->remove();
$crawler->filter('div')->setAttribute('data-test', 'ü & "');
$html = $crawler->saveHTML();
checkScripts(!str_contains($html, 'FILE:/one.js') && str_contains($html, $script), 'External script removal must still work');
checkScripts(HtmlPageCrawler::create($html)->filter('div')->getAttribute('data-test') === 'ü & "', 'HTML attribute escaping must remain intact');
echo "DOM operations: fragment insertion, external script removal and normal HTML escaping PASS\n";

$original = \Wa72\HtmlPageDom\HtmlPageCrawler::create('<div></div>');
$converted = HtmlPageCrawler::create($original);
$converted->append($script);
checkScripts($converted->getNode(0) === $original->getNode(0) && str_contains($converted->saveHTML(), $script), 'Wrapping existing crawlers must retain their DOM nodes');

// Exercise actual editables with scripts already present and scripts inserted from CMS content.
define('MULTI_LANGUAGE', false);
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/EditableInterface.php';
require CORE_DIR . 'cms/extensions/Core/System/classes/Editables/Headline/Editable.php';

class ScriptTestTexts extends \Frootbox\Persistence\Content\Repositories\Texts
{
    public function __construct(public array $texts = []) {}
    public function fetchOne(array $params = null, array $options = null): ?\Frootbox\Db\Row
    {
        $text = $this->texts[$params['where']['uid']] ?? null;
        return $text === null ? null : new \Frootbox\Persistence\Content\Text(['text' => $text]);
    }
}

$config = new \Frootbox\Config\Config();
$texts = new ScriptTestTexts();
$headline = new \Frootbox\Ext\Core\System\Editables\Headline\Editable();
$html = $headline->parse('<main><h2 data-editable data-uid="test:title">Überschrift</h2>' . $script . '</main>', $config, $texts);
checkScripts(str_contains($html, $script) && str_contains($html, '<header'), 'Headline rendering must preserve scripts and still transform headings');
echo "Editable integration: headline transformation with unchanged script PASS\n";

class ScriptTestFiles extends \Frootbox\Persistence\Repositories\Files
{
    public function __construct() {}
}
class ScriptTestWidgets extends \Frootbox\Persistence\Content\Repositories\Widgets
{
    public function __construct() {}
}
class ScriptTestPages extends \Frootbox\Persistence\Repositories\Pages
{
    public function __construct() {}
}
class ScriptTestTranslatorFactory extends \Frootbox\TranslatorFactory
{
    public function __construct() {}
    public function get($language): \Frootbox\Translation\Translator
    {
        return new \Frootbox\Translation\Translator();
    }
}

$temp = sys_get_temp_dir() . '/frootbox-script-test-' . bin2hex(random_bytes(8)) . '/';
mkdir($temp);
register_shutdown_function(static function () use ($temp): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temp);
});
define('FILES_DIR', $temp);
define('SERVER_PATH', '/');
define('SERVER_PATH_PROTOCOL', 'https://example.test/');
define('REQUEST', 'test');
define('SCRIPT_NONCE', 'test-nonce');
define('SIGNING_TOKEN', 'test-token');
define('GLOBAL_LANGUAGE', 'de-DE');

file_put_contents($temp . 'bundle.js', 'window.bundleTest = "Büro";');
$json = '{"title":"Büro – 日本語 😀","description":"<p>für dich &amp; mich</p>"}';
$inline = '<script type="application/ld+json" nonce="json-nonce">' . $json . '</script>';
$contentScript = '<script data-content="1">const html = "<p>ü &amp;</p>";</script>';
$source = '<!doctype html><html><head><meta charset="utf-8">' . $inline . '</head><body>'
    . '<h2 data-editable data-uid="test:title">Überschrift</h2>'
    . '<div data-editable data-uid="test:content"></div><a href="/">Link</a>'
    . $script . '<script src="FILE:' . $temp . 'bundle.js"></script></body></html>';
$config = new \Frootbox\Config\Config();
$config->append([
    'filesRootFolder' => $temp,
    'publicCacheDir' => 'files/',
    'editables' => [['editable' => '\\Frootbox\\Ext\\Core\\System\\Editables\\Headline']],
]);
$container = new \DI\Container();
$container->set(\Frootbox\Config\Config::class, $config);
$container->set(\Frootbox\Persistence\Content\Repositories\Texts::class, new ScriptTestTexts(['test:content' => '<p>Text</p>' . $contentScript]));
$container->set(\Frootbox\Persistence\Repositories\Files::class, new ScriptTestFiles());
$container->set(\Frootbox\Persistence\Content\Repositories\Widgets::class, new ScriptTestWidgets());
$parser = new \Frootbox\View\HtmlParser($source, $container);
$html = $parser->parse($config, new \Frootbox\Payload(), $container, new ScriptTestTranslatorFactory(), new ScriptTestPages());
checkScripts(str_contains($html, $inline) && str_contains($html, $script) && str_contains($html, $contentScript), 'The complete HTML pipeline must preserve inline and inserted scripts');
checkScripts(!str_contains($html, 'FROOTBOX_SCRIPT_') && !str_contains($html, 'FILE:'), 'Internal markers must not leak into the page');
$crawler = HtmlPageCrawler::create($html);
checkScripts($crawler->filter('header h2')->count() === 1 && $crawler->filter('a')->getAttribute('tabindex') === '0', 'Regular CMS DOM changes must still run');
checkScripts(json_decode($crawler->filter('script[type="application/ld+json"]')->text(null, false), true, 512, JSON_THROW_ON_ERROR)['title'] === 'Büro – 日本語 😀', 'Rendered JSON-LD must retain Unicode');
$bundled = $crawler->filter('script[src]');
checkScripts($bundled->count() === 1 && $bundled->getAttribute('nonce') === SCRIPT_NONCE, 'JavaScript bundling and nonce must remain intact');
$bundlePath = str_replace(SERVER_PATH_PROTOCOL . 'files/', $temp, $bundled->getAttribute('src'));
checkScripts(is_file($bundlePath) && str_contains(file_get_contents($bundlePath), 'window.bundleTest'), 'Bundled JavaScript must contain the original external file');
echo "Full HTML pipeline: JSON-LD, inline JavaScript, editable content, headings, links and script bundling PASS\n";

// Dependent untouched assets must keep defer (and order) through the pipeline.
// In particular, the small headline adapter must never run before ContentTools.
$assetHelper = new \Frootbox\View\Viewhelper\View($container);
$deferredTag = HtmlPageCrawler::create($assetHelper->asset('EXT:Core/Editing/contenttools/content-tools.js', 'script', false, true));
checkScripts($deferredTag->getAttribute('defer') !== null && $deferredTag->getAttribute('data-untouched') === '1', 'The asset helper must preserve deferred assets outside the async bundle');
$defaultTag = HtmlPageCrawler::create($assetHelper->asset('EXT:Core/Editing/contenttools/content-tools.js', 'script', true));
checkScripts($defaultTag->getAttribute('defer') === null, 'Existing asset calls keep their loading behavior');
file_put_contents($temp . 'dependency.js', 'window.editorDependency = true;');
file_put_contents($temp . 'dependent.js', 'if (!window.editorDependency) { throw new Error("Dependency missing"); }');
file_put_contents($temp . 'independent.js', 'window.independentScript = true;');
$orderedAssets = '<script defer data-untouched="1" src="FILE:' . $temp . 'dependency.js"></script>'
    . '<script defer data-untouched="1" src="FILE:' . $temp . 'dependent.js"></script>'
    . '<script data-untouched="1" src="FILE:' . $temp . 'independent.js"></script>';

foreach (['<!doctype html><html><head></head><body>' . $orderedAssets . '</body></html>', '<main>Fragment</main>' . $orderedAssets] as $source) {
    $parser = new \Frootbox\View\HtmlParser($source, $container);
    $html = $parser->parse($config, new \Frootbox\Payload(), $container, new ScriptTestTranslatorFactory(), new ScriptTestPages());
    $scripts = HtmlPageCrawler::create($html)->filter('script[data-untouched][src]');
    checkScripts($scripts->count() === 3, 'All untouched assets must survive');
    foreach (['dependency.js', 'dependent.js', 'independent.js'] as $index => $filename) {
        $asset = $scripts->eq($index);
        checkScripts(str_ends_with($asset->getAttribute('src'), '-' . $filename), 'Untouched script order must be preserved');
        checkScripts($asset->getAttribute('nonce') === SCRIPT_NONCE, 'Untouched scripts must retain the CSP nonce');
        if ($index < 2) {
            checkScripts($asset->getAttribute('defer') !== null && $asset->getAttribute('async') === null, 'Dependent scripts must execute in document order using defer, without async');
        } else {
            checkScripts($asset->getAttribute('async') !== null, 'Independent scripts retain their existing async behavior');
        }
    }
}
echo "Script dependencies: defer, source order, nonce and async compatibility in pages and fragments PASS\n";

// Direct template images must receive disclosures and usable bundled assets.
$parser = new \Frootbox\View\HtmlParser('<html><head></head><body><a data-fancybox="gallery"><img data-frootbox-ai="1" src="/image.jpg" alt="Landscape"></a></body></html>', $container);
$html = $parser->parse($config, new \Frootbox\Payload(), $container, new ScriptTestTranslatorFactory(), new ScriptTestPages());
$crawler = HtmlPageCrawler::create($html);
checkScripts($crawler->filter('[data-frootbox-ai-label]')->count() === 1, 'Final page must include one AI disclosure');
checkScripts(!str_contains($html, 'FILE:'), 'AI assets must go through the regular asset pipeline');
$bundlePath = str_replace(SERVER_PATH_PROTOCOL . 'files/', $temp, $crawler->filter('script[src]')->getAttribute('src'));
checkScripts(is_file($bundlePath) && str_contains(file_get_contents($bundlePath), 'frootboxAiLightboxInstalled'), 'AI lightbox integration must be bundled');
echo "AI full HTML pipeline: direct images, disclosure and bundled lightbox integration PASS\n";
