<?php

// Run: php tests/job_json_ld_test.php [path/to/Robin2026]
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');
require CORE_DIR . 'cms/extensions/Core/HelpAndSupport/classes/Plugins/Jobs/Partials/GoogleJsonLd/Partial.php';

function checkJobJsonLd(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

class JobJsonLdTexts extends \Frootbox\Persistence\Content\Repositories\Texts
{
    public function __construct(public array $texts) {}

    public function fetchByUid($uid, array $options = null)
    {
        return isset($this->texts[$uid])
            ? new \Frootbox\Persistence\Content\Text(['text' => $this->texts[$uid]])
            : null;
    }
}

class JobJsonLdEntity
{
    public string $subtitle = 'für Büromanagement';

    public function __construct(private string $prefix, public ?bool $enabled = null) {}
    public function getUid(string $field): string { return $this->prefix . ':' . $field; }
    public function getTitle($language = null): string { return 'Kaufmann/Kauffrau'; }
    public function getSubtitle(): string { return $this->subtitle; }
    public function getDate(): string { return '2026-10-01'; }
    public function getLanguageAliases(): array { return []; }
    public function getGlobalConfig(string $key): ?bool
    {
        checkJobJsonLd($key === 'Google.StructuredData', 'Use the existing opt-in key');
        return $this->enabled;
    }
    public function __call($method, $arguments) { return null; }
}

class JobJsonLdView
{
    public int $partialCalls = 0;

    public function __construct(
        private \Twig\Environment $twig,
        private \Frootbox\Config\Config $config,
        private JobJsonLdTexts $texts,
        private \Frootbox\View\Viewhelper\Json $json,
    ) {}

    public function getViewhelper($name, $options = [])
    {
        return $name === 'Partials' ? $this : new class {
            public function getTranslator() { return $this; }
            public function __call($method, $arguments) { return ''; }
        };
    }

    public function set(...$arguments): string { return ''; }

    public function render(string $class, array $attributes): string
    {
        ++$this->partialCalls;
        $class .= '\\Partial';
        $partial = new $class();
        $partial->setAttributes($attributes);
        return $this->twig->render('Partials/GoogleJsonLd/resources/private/views/View.html.twig', $partial->onBeforeRendering($this->config, $this->texts, $this->json));
    }
}

$config = new \Frootbox\Config\Config();
$config->append(['Ext' => ['Core' => ['HelpAndSupport' => ['Jobs' => ['Google' => [
    'HiringOrganization' => ['Name' => 'Test GmbH', 'Url' => 'https://example.test'],
]]]]]]);
$specialText = "<p class=\"intro\">Büro &amp; Organisation: \"Team\", C:\\Jobs\n</script><script>test</script></p>";
$texts = new JobJsonLdTexts([
    'job:teaser' => $specialText,
    'job:text-above-columns' => '<p>Einleitung</p>',
    'job:text-tasks' => '<ul><li>Aufgaben</li></ul>',
    'job:text-profile' => '<p>Profil</p>',
    'job:text-benefits' => '<p>Benefits</p>',
    'plugin:text-global-1' => '<p>Global 1</p>',
    'plugin:text-global-2' => '<p>Global 2</p>',
    'job:text-below' => '<p>Abschluss</p>',
    'job:text' => '<p>ShowJob02 Beschreibung</p>',
    'job:empty' => '   ',
]);
$job = new JobJsonLdEntity('job');
$plugin = new JobJsonLdEntity('plugin');
$root = CORE_DIR . 'cms/extensions/Core/HelpAndSupport/classes/Plugins/Jobs/';
$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($root), ['cache' => false]);
$container = new \DI\Container();
$json = $container->get(\Frootbox\View\Viewhelper\Json::class);
$view = new JobJsonLdView($twig, $config, $texts, $json);
$layouts = ['Layouts/ShowJob01/View.html.twig'];
if (isset($argv[1])) {
    $twig->getLoader()->addPath($argv[1], 'customer');
    $layouts[] = '@customer/plugins/Core/HelpAndSupport/Jobs/ShowJob01/View.html.twig';
}

foreach ($layouts as $layout) {
    foreach ([null, false, true] as $enabled) {
        foreach ([0, 1, 2, 3] as $blocks) {
            $plugin->enabled = $enabled;
            $view->partialCalls = 0;
            $html = $twig->render($layout, [
                'view' => $view, 'job' => $job, 'plugin' => $plugin,
                'globalLanguage' => 'de-DE',
                'variables' => [
                    'skipSocialSharing' => true,
                    'withTextAboveColumns' => $blocks > 1,
                    'predefinedTextblocks' => $blocks,
                    'predefinedGlobalTextblocks' => min(2, $blocks),
                ],
            ]);
            // The CMS serializes both fragments and the complete page after Twig.
            $html = \Frootbox\View\HtmlPageCrawler::create($html)->saveHTML();
            $html = \Frootbox\View\HtmlPageCrawler::create(
                '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>'
            )->saveHTML();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $scripts = (new DOMXPath($dom))->query('//script[@type="application/ld+json"]');
            checkJobJsonLd($view->partialCalls === ($enabled ? 1 : 0), "$layout: render only with opt-in");
            checkJobJsonLd($scripts->length === ($enabled ? 1 : 0), "$layout: exactly one JSON-LD script with opt-in");
            if (!$enabled) {
                continue;
            }
            checkJobJsonLd($dom->getElementsByTagName('script')->length === 1, 'Description must not break out of the script');
            // Parse the original UTF-8 script content; DOMDocument assumes Latin-1 without a charset tag.
            preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);
            $data = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
            checkJobJsonLd($data['@type'] === 'JobPosting', 'JobPosting type');
            checkJobJsonLd($data['title'] === 'Kaufmann/Kauffrau für Büromanagement', 'Preserve Unicode');
            checkJobJsonLd(str_contains($data['description'], $specialText), 'Preserve quotes, slashes, HTML and newlines');
            foreach (['Aufgaben' => 1, 'Profil' => 2, 'Benefits' => 3, 'Global 1' => 1, 'Global 2' => 2, 'Einleitung' => 2] as $text => $minimum) {
                checkJobJsonLd(str_contains($data['description'], $text) === ($blocks >= $minimum), "$layout: include only visible text: $text");
            }
            checkJobJsonLd(str_ends_with($data['description'], '<p>Abschluss</p>'), 'Include closing text');
            checkJobJsonLd(!str_contains($data['description'], 'ShowJob02'), 'Do not include a hidden legacy field');
            checkJobJsonLd($data['datePosted'] === '2026-10-01' && !isset($data['validThrough']), 'Keep publication date without inventing an expiry date');
        }
    }
    echo "$layout: opt-in and description cases PASS\n";
}

$partial = new \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Partials\GoogleJsonLd\Partial();
foreach ([null, ['missing', 'job:empty', 'job:text']] as $uids) {
    $attributes = ['Job' => $job];
    if ($uids !== null) {
        $attributes['DescriptionUids'] = $uids;
    }
    $partial->setAttributes($attributes);
    $data = json_decode($partial->onBeforeRendering($config, $texts, $json)['StructuredData'], true, 512, JSON_THROW_ON_ERROR);
    checkJobJsonLd($data['description'] === '<p>ShowJob02 Beschreibung</p>', 'Legacy fallback and missing/empty text handling');
}
echo "Shared partial: legacy description and empty fields PASS\n";

$job->subtitle = "f&uuml;r&nbsp;B&#252;romanagement \r\n\t(m/w/d)";
$partial->setAttributes(['Job' => $job]);
$data = json_decode($partial->onBeforeRendering($config, $texts, $json)['StructuredData'], true, 512, JSON_THROW_ON_ERROR);
checkJobJsonLd($data['title'] === 'Kaufmann/Kauffrau für Büromanagement (m/w/d)', 'Decode existing entities and normalize title whitespace');
echo "Shared partial: title normalization PASS\n";

// Exercise the reusable viewhelper through Twig and both DOM serialization paths.
$payload = [
    'title' => "Büro & Planung \"Team\" – 日本語 😀",
    'html' => "<p>5 &lt; 10 &amp; Team's</p>\n</script><script>alert(1)</script>",
    'path' => 'C:\\Jobs',
    'values' => [null, true, false, 42, -1, 1.5],
];
$script = $twig->createTemplate('<script type="application/ld+json">{{ json.encode(data) | raw }}</script>')
    ->render(['json' => $json, 'data' => $payload]);
foreach ([$script, '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $script . '</body></html>'] as $html) {
    for ($pass = 0; $pass < 3; ++$pass) {
        $html = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html)->saveHTML();
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $scripts = $dom->getElementsByTagName('script');
        checkJobJsonLd($scripts->length === 1, 'JSON must stay inside a single script');
        checkJobJsonLd(json_decode($scripts->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR) === $payload, 'JSON payload must survive DOM serialization unchanged');
    }
}
echo "JSON viewhelper: Twig, Unicode, HTML and data types survive DOM serialization PASS\n";
