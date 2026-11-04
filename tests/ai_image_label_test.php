<?php
// Run with: php tests/ai_image_label_test.php
require __DIR__ . '/inline_image_refresh_test.php';

use Frootbox\View\AiImageLabel;
use Frootbox\View\HtmlPageCrawler;

function checkAi(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function aiCount(string $html, string $selector): int {
    return HtmlPageCrawler::create($html)->filter($selector)->count();
}

$plain = '<html><head></head><body><img src="/photo.jpg" alt="Original"></body></html>';
checkAi(AiImageLabel::render($plain) === $plain, 'Unmarked markup must remain unchanged.');
$html = AiImageLabel::render('<html><head></head><body><a data-fancybox="gallery" data-caption="Existing caption"><picture class="fluid"><source srcset="/large.jpg"><img data-frootbox-ai="1" src="/image.jpg" alt="Description"></picture></a></body></html>');
checkAi(aiCount($html, '[data-frootbox-ai-label]') === 1, 'Expected one visible disclosure.');
checkAi(aiCount($html, 'picture > source') === 1 && aiCount($html, 'picture > img') === 1, 'Picture/source semantics must remain intact.');
checkAi(aiCount($html, 'a[data-frootbox-ai="1"][data-caption="Existing caption"]') === 1, 'Lightbox metadata must preserve captions.');
checkAi(aiCount($html, 'style[data-frootbox-ai-styles]') === 1, 'Standard styling missing.');
checkAi(aiCount($html, 'script[data-frootbox-ai-script]') === 1, 'Lightbox integration missing.');
$twice = AiImageLabel::render($html);
checkAi(aiCount($twice, '[data-frootbox-ai-label]') === 1 && aiCount($twice, '[data-frootbox-ai-host]') === 1, 'Repeated parsing must not nest wrappers or duplicate labels.');
checkAi(aiCount($twice, 'style[data-frootbox-ai-styles]') === 1 && aiCount($twice, 'script[data-frootbox-ai-script]') === 1, 'Repeated parsing duplicated assets.');
$cleared = AiImageLabel::render(str_replace('data-frootbox-ai="1"', 'data-frootbox-ai="0"', $twice));
checkAi(aiCount($cleared, '[data-frootbox-ai-label]') === 0 && aiCount($cleared, '[data-frootbox-ai-host]') === 0, 'Clearing the flag must remove disclosure and wrapper.');

foreach ([[0,0], [0,1], [1,0], [1,1]] as [$mobile, $desktop]) {
    $html = AiImageLabel::render('<html><head></head><body><section role="img" aria-label="Landscape" data-frootbox-ai="' . $mobile . '" data-frootbox-ai-desktop="' . $desktop . '"><h1>Keep heading</h1></section></body></html>');
    checkAi(aiCount($html, '[data-frootbox-ai-label]') === (int) ($mobile || $desktop), 'Wrong responsive label count.');
    checkAi(aiCount($html, '.frootbox-ai-desktop-only') === (int) (!$mobile && $desktop), 'Wrong desktop-only disclosure.');
    checkAi(aiCount($html, '.frootbox-ai-mobile-only') === (int) ($mobile && !$desktop), 'Wrong mobile-only disclosure.');
    checkAi(str_contains($html, 'Keep heading'), 'Background content lost.');
    if ($mobile || $desktop) checkAi(aiCount($html, 'section[role="group"][aria-label="Landscape"]') === 1, 'Disclosure must be exposed to assistive technology.');
}
$html = AiImageLabel::render('<a data-frootbox-ai-container data-frootbox-ai="1"><img src="/image.jpg"></a>');
checkAi(aiCount($html, 'a > img') === 1 && aiCount($html, 'a > [data-frootbox-ai-label]') === 1, 'Justified gallery must keep its direct image child.');

class AiImages extends InlineImageFiles {
    public function __construct(public bool $mobile, public ?bool $desktop = null) {}
    public function fetchByUid($uid, array $options = null) {
        if ($uid === 'hero-desktop' && $this->desktop === null) return null;
        return new InlineImageFile(['id' => $uid === 'hero-desktop' ? 2 : 1, 'isAiGenerated' => $uid === 'hero-desktop' ? $this->desktop : $this->mobile]);
    }
}
foreach ([false, true] as $flag) {
    $image = (new Frootbox\Ext\Core\Images\Editables\Image\Editable())->parse('<picture data-editable data-uid="image"><img width="600" height="400" src="/placeholder.jpg"></picture>', new Frootbox\Config\Config(), new AiImages($flag));
    checkAi(aiCount($image, '[data-frootbox-ai-label]') === (int) $flag, 'Editable renderer did not use persisted flag.');
}
foreach ([[false,true], [true,false], [true,null]] as [$mobile,$desktop]) {
    $html = (new Frootbox\Ext\Core\Images\Editables\Background\Editable())->parse('<html><head></head><body><div data-type="Images/Background" data-uid="hero"></div></body></html>', new AiImages($mobile,$desktop), new Frootbox\Config\Config());
    checkAi(aiCount($html, '.frootbox-ai-desktop-only') === (int) (!$mobile && $desktop), 'Background renderer did not propagate desktop flag.');
    checkAi(aiCount($html, '.frootbox-ai-mobile-only') === (int) ($mobile && $desktop === false), 'Background renderer did not propagate mobile flag.');
}
checkAi(AiImageLabel::attributes(new InlineImageFile(['isAiGenerated' => 1])) === 'data-frootbox-ai="1"', 'Custom layout helper must expose flag.');
checkAi(AiImageLabel::attributes(null) === 'data-frootbox-ai="0"', 'Missing files must be safe.');

// Parse all bundled image templates to catch syntax errors in both direct and editable layouts.
$loader = new Twig\Loader\FilesystemLoader(CORE_DIR);
$twig = new Twig\Environment($loader);
$helper = new Frootbox\View\Viewhelper\Files(new DI\Container());
$custom = $twig->createTemplate('<img {{ files.getAiImageAttributes(image)|raw }} src="/image.jpg">')->render([
    'files' => $helper, 'image' => new InlineImageFile(['isAiGenerated' => 1]),
]);
checkAi(aiCount(AiImageLabel::render($custom), '[data-frootbox-ai-label]') === 1, 'Twig helper integration must render a disclosure.');
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CORE_DIR . 'cms/extensions/Core/Images/classes')) as $file) {
    if ($file->isFile() && str_contains($file->getPathname(), '/Layouts/') && str_ends_with($file->getFilename(), '.twig')) {
        $twig->parse($twig->tokenize(new Twig\Source(file_get_contents($file->getPathname()), $file->getPathname())));
    }
}
echo "AI labels: image/background rendering, responsive flags, repeat/reset, lightbox metadata, gallery DOM, helpers and Twig syntax PASS\n";

foreach ([[0,0,'0',''], [0,1,'edited','Mit KI bearbeitet'], [1,0,'1','KI-generiert'], [1,1,'1','KI-generiert']] as [$generated,$edited,$type,$text]) {
    $file = new InlineImageFile(['isAiGenerated' => $generated, 'isAiEdited' => $edited]);
    checkAi($file->getAiLabelType() === $type, 'Generated must take precedence over edited.');
    $html = AiImageLabel::render('<a data-fancybox="gallery"><img ' . AiImageLabel::attributes($file) . ' src="/image.jpg"></a>');
    $labels = HtmlPageCrawler::create($html)->filter('[data-frootbox-ai-label]');
    checkAi($labels->count() === (int) ($type !== '0'), 'Wrong disclosure count for metadata combination.');
    if ($text) {
        checkAi($labels->text() === $text, 'Wrong disclosure text.');
        checkAi(aiCount($html, 'a[data-frootbox-ai="' . $type . '"]') === 1, 'Lightbox must receive disclosure type.');
    }
}
foreach ([['1','edited'], ['edited','1']] as [$mobile,$desktop]) {
    $html = AiImageLabel::render('<div data-frootbox-ai="' . $mobile . '" data-frootbox-ai-desktop="' . $desktop . '"></div>');
    $crawler = HtmlPageCrawler::create(AiImageLabel::render($html));
    checkAi($crawler->filter('[data-frootbox-ai-label]')->count() === 2, 'Different responsive types need separate labels without duplicates.');
    checkAi($crawler->filter('.frootbox-ai-mobile-only')->text() === ($mobile === '1' ? 'KI-generiert' : 'Mit KI bearbeitet'), 'Wrong mobile text.');
    checkAi($crawler->filter('.frootbox-ai-desktop-only')->text() === ($desktop === '1' ? 'KI-generiert' : 'Mit KI bearbeitet'), 'Wrong desktop text.');
}
echo "AI edited labels: all metadata combinations, generated precedence and mixed responsive types PASS\n";
