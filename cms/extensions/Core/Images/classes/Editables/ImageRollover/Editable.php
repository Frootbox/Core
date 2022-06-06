<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\ImageRollover;

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
        $counter = 0;

        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('picture[data-editable-rollover]')->each(function ( $element ) use ($files, &$counter) {

            $uid = $element->getAttribute('data-uid');

            $result = $files->fetch([
                'where' => [
                    'uid' => $uid,
                    'language' => $_SESSION['frontend']['language'],
                ],
                'order' => [
                    'orderID DESC',
                ],
                'limit' => 2,
            ]);

            if ($result->getCount() == 0 and $_SESSION['frontend']['language'] != DEFAULT_LANGUAGE) {

                $result = $files->fetch([
                    'where' => [
                        'uid' => $uid,
                        'language' => DEFAULT_LANGUAGE,
                    ],
                    'order' => [
                        'orderID DESC',
                    ],
                    'limit' => 2,
                ]);
            }

            if ($result->getCount() == 0) {

                if (!empty($element->getAttribute('data-skipempty')) and !defined('EDITING')) {
                    $element->remove();
                }

                return;
            }

            $file = $result->current();

            $innerHtml = trim($element->getInnerHtml());

            $payload = [
                'type' => 'jpg',
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

            if ($result->getCount() > 1) {

                $result->next();
                $swapFile = $result->current();

                $swapSrc = $swapFile->getUri($payload);
            }
            else {
                $swapSrc = null;
            }

            $html = '
                <img data-swap="' . $swapSrc . '" data-default="' . $default . '" class="' . $class . '" src="' . $file->getUri($payload) . '" ' . ($payload['height'] ? 'height="' . $payload['height'] . '"' : '') . ' ' . ($payload['width'] ? 'width="' . $payload['width'] . '"' : '') . ' alt="' . $file->getAlt() . '" />
            ';

            $element->setInnerHtml($html);

            ++$counter;
        });

        $html = $crawler->saveHTML();

        if ($counter > 0) {
            $html = str_replace('</head>', '<script src="FILE:' . __DIR__ . '/resources/public/javascript/init.js' . '"></script>', $html);
        }

        return $html;
    }
}
