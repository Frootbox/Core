<?php

// Run with: php tests/youtube_embed_test.php
require dirname(__DIR__) . '/lib/autoload.php';
define('CORE_DIR', dirname(__DIR__) . '/');
require CORE_DIR . 'cms/classes/Front.php';
spl_autoload_register('Frootbox\\Front::autoload');

use Frootbox\View\HtmlPageCrawler;
use Frootbox\View\YoutubeEmbed;

$videoId = 'xtYH2DEBpO0';
$embed = 'https://www.youtube.com/embed/' . $videoId;
$sharedUrl = 'https://www.youtube.com/watch?si=jZ4_TzbxO9x61GSM&v=' . $videoId . '&feature=youtu.be';
$cases = [
    [$sharedUrl, $embed],
    ['https://www.youtube.com/watch?v=' . $videoId . '&si=tracking', $embed],
    ['https://youtube.com/watch?v=' . $videoId, $embed],
    ['https://m.youtube.com/watch?v=' . $videoId, $embed],
    ['https://music.youtube.com/watch?v=' . $videoId, $embed],
    ['https://youtu.be/' . $videoId . '?si=tracking', $embed],
    ['https://youtu.be/Ab_cd-Ef123', 'https://www.youtube.com/embed/Ab_cd-Ef123'],
    ['https://www.youtube.com/shorts/' . $videoId, $embed],
    ['https://youtube.com/live/' . $videoId, $embed],
    ['//www.youtube.com/watch?v=' . $videoId, $embed],
    [' http://WWW.YouTube.com/watch?v=' . $videoId . ' ', $embed],
    ['https://www.youtube-nocookie.com/watch?v=' . $videoId, 'https://www.youtube-nocookie.com/embed/' . $videoId],
    ['https://youtu.be/' . $videoId . '?t=90', $embed . '?start=90'],
    ['https://youtu.be/' . $videoId . '?t=90s', $embed . '?start=90'],
    ['https://youtu.be/' . $videoId . '?t=1h2m3s', $embed . '?start=3723'],
    ['https://youtu.be/' . $videoId . '#t=2m', $embed . '?start=120'],
    ['https://youtu.be/' . $videoId . '?start=30&t=90', $embed . '?start=30'],
    ['https://youtu.be/' . $videoId . '?t=0', $embed . '?start=0'],
    ['https://youtu.be/' . $videoId . '?t=invalid', $embed],
    ['https://www.youtube.com/watch?v=' . $videoId . '&autoplay=1&loop=1&playlist=' . $videoId, $embed . '?autoplay=1&loop=1&playlist=' . $videoId],
    ['https://youtu.be/' . $videoId . '?t[]=90', $embed],
];

foreach ([
    $embed . '?rel=0&start=12',
    'https://www.youtube-nocookie.com/embed/' . $videoId . '?autoplay=1',
    'https://www.youtube.com/embed/videoseries?list=PL123',
    'https://www.youtube.com/watch?si=tracking',
    'https://www.youtube.com/watch?v[]=xtYH2DEBpO0',
    'https://www.youtube.com/watch?v=invalid',
    'https://www.youtube.com/shorts/' . $videoId . '/extra',
    'https://youtu.be/' . $videoId . '/extra',
    'https://youtube.com.example.org/watch?v=' . $videoId,
    'https://example.org/watch?v=' . $videoId,
    'https://www.youtube.com@evil.test/watch?v=' . $videoId,
    'https://user@www.youtube.com/watch?v=' . $videoId,
    'ftp://www.youtube.com/watch?v=' . $videoId,
    '/watch?v=' . $videoId,
    'about:blank',
    '',
] as $unchanged) {
    $cases[] = [$unchanged, $unchanged];
}

foreach ($cases as [$url, $expected]) {
    $actual = YoutubeEmbed::normalizeUrl($url);
    if ($actual !== $expected || YoutubeEmbed::normalizeUrl($actual) !== $actual) {
        throw new RuntimeException('Unexpected embed URL for ' . $url . ': ' . $actual);
    }
}
echo 'YouTube URL normalization: ' . count($cases) . " cases PASS\n";

// Exercise the final HTML rendering pass without database or asset compilation.
class YoutubeTestParser extends \Frootbox\View\HtmlParser
{
    public function parseEditables(
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\TranslatorFactory $translationFactory
    ): void {}

    public function rewriteTags(
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\TranslatorFactory $translationFactory
    ): void {}
}

define('SERVER_PATH', '/');
define('SERVER_PATH_PROTOCOL', 'https://example.test/');
define('REQUEST', 'test');
define('SCRIPT_NONCE', 'test-nonce');
define('SIGNING_TOKEN', 'test-token');

$escapedUrl = htmlspecialchars($sharedUrl, ENT_QUOTES, 'UTF-8');
$script = '<script>const title = "Büro"; const compare = 1 < 2;</script>';
$source = '<!doctype html><html><head></head><body>'
    . '<iframe id="active" src="' . $escapedUrl . '" allowfullscreen loading="lazy"></iframe>'
    . '<iframe id="blocked" data-consent="youtube-video" data-src="' . $escapedUrl . '"></iframe>'
    . '<iframe id="placeholder" src="about:blank" data-consent="youtube-video" data-src="' . $escapedUrl . '"></iframe>'
    . '<iframe id="other" src="https://example.test/player"></iframe>'
    . '<a href="' . $escapedUrl . '">YouTube</a>' . $script . '</body></html>';
$container = new \DI\Container();
$parser = new YoutubeTestParser($source, $container);
$html = $parser->parse(
    new \Frootbox\Config\Config(), new \Frootbox\Payload(), $container,
    (new ReflectionClass(\Frootbox\TranslatorFactory::class))->newInstanceWithoutConstructor(),
    (new ReflectionClass(\Frootbox\Persistence\Repositories\Pages::class))->newInstanceWithoutConstructor(),
);
$crawler = HtmlPageCrawler::create($html);
$active = $crawler->filter('#active');
$blocked = $crawler->filter('#blocked');
$placeholder = $crawler->filter('#placeholder');

if ($active->getAttribute('src') !== $embed || $active->getAttribute('allowfullscreen') === null || $active->getAttribute('loading') !== 'lazy'
    || $blocked->getAttribute('src') !== null || $blocked->getAttribute('data-src') !== $embed || $blocked->getAttribute('data-consent') !== 'youtube-video'
    || $placeholder->getAttribute('src') !== 'about:blank' || $placeholder->getAttribute('data-src') !== $embed
    || $crawler->filter('#other')->getAttribute('src') !== 'https://example.test/player'
    || $crawler->filter('a')->getAttribute('href') !== $sharedUrl || !str_contains($html, $script)) {
    throw new RuntimeException('Iframe normalization must preserve consent, other attributes, links and scripts.');
}
echo "HTML rendering: active and consent-gated iframes, unchanged links and scripts PASS\n";
