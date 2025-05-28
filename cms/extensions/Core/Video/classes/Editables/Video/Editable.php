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
        $crawler->filter('video[data-editable-video][data-uid]')->each(function ( $element ) use ($fileRepository, $textRepository, $configuration) {

            // Obtain uid
            $uid = $element->getAttribute('data-uid');

            // Fetch text
            $text = $textRepository->fetchByUid($uid, [
                'createOnMiss' => true,
            ]);

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
