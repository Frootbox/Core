<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Video\Editables\Video;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{
    use \Frootbox\Http\Traits\UrlSanitize;

    protected $type = 'NonStructural';

    /**
     * Extract a YouTube video id from the commonly shared URL formats.
     */
    public static function getYoutubeVideoId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('#^www\.#', '', $host);
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = trim($parts['path'] ?? '', '/');
        }
        elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            $path = trim($parts['path'] ?? '', '/');

            if ($path === 'watch') {
                parse_str($parts['query'] ?? '', $query);
                $videoId = $query['v'] ?? null;
            }
            elseif (preg_match('#^(?:embed|shorts|live)/([^/]+)#', $path, $match)) {
                $videoId = $match[1];
            }
        }

        return is_string($videoId) && preg_match('#^[A-Za-z0-9_-]{11}$#', $videoId) ? $videoId : null;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function getVideoMimeTypeByFilename(string $filename): ?string {

        $videoMimeTypes = [
            'mp4'  => 'video/mp4',
            'm4v'  => 'video/x-m4v',
            'mov'  => 'video/quicktime',
            'wmv'  => 'video/x-ms-wmv',
            'flv'  => 'video/x-flv',
            'avi'  => 'video/x-msvideo',
            'webm' => 'video/webm',
            'mkv'  => 'video/x-matroska',
            '3gp'  => 'video/3gpp',
            '3g2'  => 'video/3gpp2',
            'ts'   => 'video/MP2T',
            'ogv'  => 'video/ogg',
        ];

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return $videoMimeTypes[$extension] ?? null;
    }

    /**
     *
     */
    public function parse(
        $html,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $textRepository,
    ): string
    {
        // Initialize html crawler
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('[data-editable-video][data-uid]')->each(function ( $element ) use ($fileRepository, $textRepository, $configuration) {

            // Obtain uid
            $uid = $element->getAttribute('data-uid');

            // Fetch text
            $text = $textRepository->fetchByUid($uid, [
                'createOnMiss' => true,
            ]);

            $videoId = self::getYoutubeVideoId($text->getConfig('VideoUrl'));

            if ($text->getConfig('SourceType') === 'youtube' && $videoId !== null) {
                $parameters = [
                    'autoplay' => !empty($text->getConfig('Autoplay')) ? '1' : '0',
                    'controls' => !empty($text->getConfig('Controls')) ? '1' : '0',
                    'loop' => !empty($text->getConfig('Loop')) ? '1' : '0',
                    'mute' => !empty($text->getConfig('Muted')) ? '1' : '0',
                    'playsinline' => '1',
                    'rel' => '0',
                ];

                if (!empty($text->getConfig('Loop'))) {
                    $parameters['playlist'] = $videoId;
                }

                $source = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?' . http_build_query($parameters, '', '&amp;');
                $template = '<iframe data-editable-video data-uid="' . htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') . '" '
                    . 'src="' . $source . '" title="YouTube Video" loading="lazy" '
                    . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
                    . 'allowfullscreen style="display:block;width:100%;aspect-ratio:16/9;border:0"></iframe>';

                $element->replaceWith($template);

                return;
            }

            // Fetch video source files
            $files = $fileRepository->fetch([
                'where' => [
                    'uid' => $uid,
                ],
            ]);

            // Fetch thumbnail
            $thumbnail = $fileRepository->fetchByUid($text->getUid('thumbnail'));


            $template = '<video disablepictureinpicture playsinline ';


            if (!empty($thumbnail)) {
                $template .= ' poster="' . $thumbnail->getUriDownload() . '" ';
            }

            if (!empty($text->getConfig('Muted'))) {
                $template .= ' muted ';
            }

            if (!empty($text->getConfig('Autoplay'))) {
                $template .= ' autoplay ';
            }

            if (!empty($text->getConfig('Loop'))) {
                $template .= ' loop ';
            }

            if (!empty($text->getConfig('Controls'))) {
                $template .= ' controls ';
            }

            $template .= ' data-editable-video data-uid="' . $uid . '">' . PHP_EOL;

            foreach ($files as $file) {

                $template .= '<source src="' . $file->getUriStream() . '" type="' . $this->getVideoMimeTypeByFilename($file->getName()) . '" />' . PHP_EOL;
            }

            $template .= '</video>';

            $element->replaceWith($template);

        });

        return $crawler->saveHTML();
    }
}
