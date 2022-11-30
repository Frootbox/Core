<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\Image;

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
        \Frootbox\Persistence\Repositories\Files $files
    ): string
    {
        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('picture[data-editable]')->each(function ( $element ) use ($files) {

            $uid = $element->getAttribute('data-uid');

            preg_match('#^\<(.*?) #', (string)$element, $match);

            if ($match[1] == 'picture') {

                $result = $files->fetch([
                    'where' => [ 'uid' => $uid ],
                    'limit' => 1
                ]);

                if ($result->getCount() == 0 and $element->getAttribute('data-fallback-uid') !== null) {

                    $result = $files->fetch([
                        'where' => [ 'uid' => $element->getAttribute('data-fallback-uid') ],
                        'limit' => 1
                    ]);

                }

                if ($result->getCount() == 0) {

                    if (!empty($element->getAttribute('data-skipempty')) and !defined('EDITING')) {
                        $element->remove();
                    }
                    elseif ($element->getAttribute('data-fallbacksrc') !== null) {
                        $element->children('img')->setAttribute('src', $element->getAttribute('data-fallbacksrc'));
                    }

                    return;
                }

                /* @var $file \Frootbox\Persistence\File */
                $file = $result->current();


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
                $html = '
                <img data-image-edited="true" data-default="' . $default . '" class="' . $class . '" src="' . $file->getUriThumbnail($payload) . '" ' . ($payload['height'] ? 'height="' . $payload['height'] . '"' : '') . ' ' . ($payload['width'] ? 'width="' . $payload['width'] . '"' : '') . ' alt="' . $file->getAlt() . '" />
            ';

                if (preg_match('#<img.*?usemap="\#([a-z0-1]+)".*?>#i', $innerHtml, $match)) {
                    $html = str_replace('<img', '<img usemap="#' . $match[1]. '"', $html);
                }


                if (!empty($file->getConfig('link'))) {
                    $html = '<a href="' . $file->getConfig('link') . '">' . $html . '</a>';
                }


                $element->setInnerHtml($html);
            }

        });

        return $crawler->saveHTML();
    }
}