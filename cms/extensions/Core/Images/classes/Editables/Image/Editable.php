<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\Image;

use Frootbox\Config\Config;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function parse (
        $html,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
    ): string
    {
        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('picture[data-editable]')->each(function ( $element ) use ($files, $config) {

            $uid = $element->getAttribute('data-uid');

            preg_match('#^\<(.*?) #', (string)$element, $match);

            if ($match[1] == 'picture') {

                // Fetch file
                $file = $files->fetchByUid($uid, [
                    'fallbackLanguageDefault' => true,
                    'order' => 'orderId DESC',
                ]);

                if (!$file and $element->getAttribute('data-fallback-uid') !== null) {
                    /**
                     * @var \Frootbox\Persistence\File $file
                     */
                    $file = $files->fetchByUid($element->getAttribute('data-fallback-uid'), [
                        'fallbackLanguageDefault' => true,
                        'order' => 'orderId DESC',
                    ]);
                }

                if (!$file) {

                    if ($element->getAttribute('data-skipempty') !== null and !defined('EDITING')) {
                        $element->remove();
                    }
                    elseif ($element->getAttribute('data-fallbacksrc') !== null) {
                        $element->children('img')->setAttribute('src', $element->getAttribute('data-fallbacksrc'));
                    }

                    return;
                }

                $innerHtml = trim($element->getInnerHtml());

                $payload = [
                    'type' => 'auto',
                    'height' => null,
                    'width' => null
                ];

                if (!empty($file->getConfig('width'))) {
                    $payload['width'] = $file->getConfig('width');
                }
                elseif (preg_match('#<img.*?width="([0-9]{1,})".*?>#i', $innerHtml, $match)) {
                    $payload['width'] = $match[1];
                }

                if (!empty($file->getConfig('height'))) {
                    $payload['height'] = $file->getConfig('height');
                }
                elseif (preg_match('#<img.*?height="([0-9]{1,})".*?>#i', $innerHtml, $match)) {
                    $payload['height'] = $match[1];
                }

                $default = (preg_match('#<img.*?src="(.*?)".*?>#i', $innerHtml, $match)) ? $match[1] : (string) null;

                $class = (preg_match('#<img.*?class="(.*?)".*?>#i', $innerHtml, $match)) ? $match[1] : (string) null;

                // $alt = !empty($file->getConfig('caption')) ? strip_tags($file->getConfig('caption')) : $file->getName();
                $html = '<div class="ext-core-images-image">
                    <img data-image-edited="true" ' . (!empty($file->getConfig('isPresentationOnly')) ? 'role="presentation"' : '') . ' data-default="' . $default . '" class="' . $class . '" src="' . $file->getUriThumbnail($payload) . '" ' . ($payload['height'] ? 'height="' . $payload['height'] . '"' : '') . ' ' . ($payload['width'] ? 'width="' . $payload['width'] . '"' : '') . ' alt="' . $file->getAlt(default: $element->getAttribute('data-alt')) . '" />
                ';

                if (!empty($file->getConfig('caption')) and (!empty($element->attr('data-caption')) or !empty($config->get('Ext.Core.Images.Editables.Image.Caption')))) {
                    $html .= '<div class="caption">' . nl2br($file->getConfig('caption')) . '</div>';
                }

                if (!empty($file->getCopyright()) and !empty($config->get('Ext.Core.Images.Editables.Image.Copyright'))) {
                    $html .= '<div class="copyright">' . nl2br($file->getCopyright()) . '</div>';
                }

                if ($element->getAttribute('data-skiplink') === null and !empty($file->getConfig('link'))) {
                    $html = '<a href="' . $file->getConfig('link') . '">' . $html . '</a>';
                }

                $html .= '</div>';

                if (preg_match('#<img.*?usemap="\#([a-z0-1]+)".*?>#i', $innerHtml, $match)) {
                    $html = str_replace('<img', '<img usemap="#' . $match[1]. '"', $html);
                }

                $element->setInnerHtml($html);
            }

        });

        return $crawler->saveHTML();
    }
}