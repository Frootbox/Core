<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\Headline;

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

    /**
     *
     */
    public function parse(
        $html,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): string
    {
        // Initialize html crawler
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('h1[data-editable][data-uid], h2[data-editable][data-uid], h3[data-editable][data-uid], h4[data-editable][data-uid], h5[data-editable][data-uid]')->each(function ( $element ) use ($texts, $configuration) {

            $uid = $element->getAttribute('data-uid');

            // Fetch text
            $where = [
                'uid' => $uid,
            ];

            if (MULTI_LANGUAGE) {
                $where['language'] = GLOBAL_LANGUAGE;
            }

            $text = $texts->fetchOne([
                'where' => $where,
            ]);

            if (MULTI_LANGUAGE and !$text and !preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:title$#i', $uid) and $element->getAttribute('data-nolanguagefallback') === null) {

                if (GLOBAL_LANGUAGE == DEFAULT_LANGUAGE) {

                    $text = $texts->fetchOne([
                        'where' => [
                            'uid' => $uid,
                            'language' => DEFAULT_LANGUAGE,
                        ],
                    ]);
                }
                else {

                    $text = $texts->fetchOne([
                        'where' => [
                            'uid' => $uid,
                        ],
                    ]);
                }
            }

            if (!defined('EDITING') and !$text and $element->getAttribute('data-skipempty') !== null) {
                $element->remove();
                return;
            }

            $headlineText = ($text and $text->getConfig('headline')) ? nl2br($text->getConfig('headline')) : $element->getInnerHtml();


            if (!$text) {
                $classes = $tagName = $element->nodeName();
            }
            else {
                $classes = $tagName = !empty($text->getConfig('level')) ? strtolower($text->getConfig('level')) : $element->nodeName();
            }

            $classes .= ' ' . $element->getAttribute('class');

            if ($text and $text->getConfig('isVisible') === false) {
                $classes .= ' disabled-headline';
            }

            $template = '<header class="' . $classes . '" data-uid="' . $uid . '">';

            if ($text and !empty($text->getConfig('supertitle')) and empty($element->getAttribute('data-skipsupertitle'))) {
                $template .= '<p class="supertitle">' . $text->getConfig('supertitle') . '</p>';
            }

            $styles = (string) null;


            if ($text and !empty($text->getConfig('style.color'))) {
                $styles .= 'color: ' . $text->getConfig('style.color') . '; ';
            }

            if ($text and !empty($text->getConfig('style.fontSize'))) {
                $styles .= 'font-size: ' . $text->getConfig('style.fontSize') . '; ';
            }

            if ($text and !empty($text->getConfig('style.textAlign'))) {
                $styles .= 'text-align: ' . $text->getConfig('style.textAlign') . '; ';
            }

            $subtitle = null;

            if (preg_match('#<span class="subtitle">(.*?)</span>#is', $headlineText, $match)) {

                $headlineText = preg_replace('#<span class="subtitle">(.*?)</span>#is', '', $headlineText);
                $subtitle = $match[1];
            }

            $template .= '<' . $tagName . ' data-editable data-uid="' . $uid . '" style="' . $styles . '">' . $headlineText . '</' . $tagName . '>';


            if ($text and !empty($text->getConfig('subtitle')) and empty($element->getAttribute('data-skipsubline'))) {
                $template .= '<p class="subtitle">' . nl2br($text->getConfig('subtitle')) . '</p>';
            }
            elseif ($subtitle) {
                $template .= '<p class="subtitle">' . nl2br($subtitle) . '</p>';
            }

            $template .= '</header>';

            $element->replaceWith($template);

            return;


            if (!empty($element->getAttribute('data-subtitleabove')) or !empty($configuration->get('Ext.Core.System.Editables.Headline.subtitleAbove'))) {

                $element->addClass('subtitle-on-top');

                $html = (string) null;

                if (!empty($text->getConfig('subtitle'))) {
                    $html .= '<span class="subtitle">' . nl2br($text->getConfig('subtitle')) . '</span> ';
                }

                $html .= '<span class="head">' . nl2br($text->getConfig('headline')) . '</span> ';

                $element->setInnerHtml($html);
            }
            else {

                if ($element->getAttribute('data-skipsubline')) {
                    $element->setInnerHtml('<span class="head">' . nl2br($text->getConfig('headline')) . '</span> ');
                }
                else {
                    $element->setInnerHtml(nl2br($headlineText));
                }
            }

            if ($text->getConfig('isVisible') === false) {
                $element->addClass('disabled-headline');
            }

            if (!empty($text->getConfig('level'))) {
                $element->setAttribute('data-changelevel', $text->getConfig('level'));
            }

            if (!empty($text->getConfig('elementId'))) {
                $element->setAttribute('id', $text->getConfig('elementId'));
            }

            if (!empty($text->getConfig('style.textAlign'))) {
                $element->setStyle('text-align', $text->getConfig('style.textAlign'));
            }
        });

        // Replace "headlines" like taglines
        $crawler->filter('[data-editable-headline]')->each(function ( $element ) use ($texts, $configuration) {

            $uid = $element->getAttribute('data-uid');

            $result = $texts->fetch([
                'where' => ['uid' => $uid],
                'limit' => 1
            ]);

            if ($result->getCount() == 0) {
                return;
            }

            $text = $result->current();

            $element->setInnerHtml(nl2br($text->getConfig('headline')) . '<span class="subtitle">' . $text->getConfig('subtitle') . '</span> ');

        });


        // Set auto ids
        $crawler->filter('h1, h2, h3, h4, h5')->each(function ( $element ) use ($texts) {

            if (!empty($element->getAttribute('id'))) {
                return;
            }

            $sanitized = $this->getStringUrlSanitized($element->getInnerHtml());

            if (!empty($sanitized)) {

                if (!defined('EDITING') or !EDITING) {
                    $element->setAttribute('id', $sanitized);
                }
                else {
                    $element->setAttribute('data-xid', $sanitized);
                }
            }
        });


        $html = $crawler->saveHTML();

        if (!empty(preg_match_all('#<h([0-9]{1})(.*?)data-changelevel="(.*?)"(.*?)>(.*?)</h\\1>#s', $html, $matches))) {

            foreach ($matches[0] as $index => $tagline) {

                $headline = '<' . strtolower($matches[3][$index]) . ' ' . $matches[2][$index] . '>' . $matches[5][$index] . '</' . strtolower($matches[3][$index]) . '>';

                $html = str_replace($tagline, $headline, $html);
            }
        }

        return $html;
    }
}
