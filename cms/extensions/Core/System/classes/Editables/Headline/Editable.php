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
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $html
     * @param \Frootbox\Config\Config $configuration
     * @param \Frootbox\Persistence\Content\Repositories\Texts $texts
     * @return string
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

            if (MULTI_LANGUAGE and !$text and (!preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:title$#i', $uid) or $element->text() == 'Überschrift') and $element->getAttribute('data-nolanguagefallback') === null) {

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
            $headlineText = preg_replace("/\r/", "", $headlineText);


            if (!$text) {
                $classes = $tagName = $element->nodeName();
            }
            else {
                $classes = $tagName = !empty($text->getConfig('level')) ? strtolower($text->getConfig('level')) : $element->nodeName();
            }

            $classes .= ' ' . $element->getAttribute('class');

            if ($text and $text->getConfig('isVisible') === false) {

                if (!defined('EDITING')) {
                    $element->remove();
                    return;
                }

                $classes .= ' disabled-headline';
            }

            $addedHtml = (string) null;

            if ($element->getAttribute('data-skipsupertitle') !== null) {
                $addedHtml = ' data-skipsupertitle ';
            }

            $styles = (string) null;



            if ($text and !empty($text->getConfig('style.color'))) {
                $styles .= 'color: ' . $text->getConfig('style.color') . '; ';
            }

            if ($text and !empty($text->getConfig('style.fontSize'))) {
                $styles .= 'font-size: ' . $text->getConfig('style.fontSize') . '; ';
            }

            if ($text and !empty($text->getConfig('style.textAlign'))) {
                $classes .= ' ' . $text->getConfig('style.textAlign');
                $styles .= 'text-align: ' . $text->getConfig('style.textAlign') . '; ';
            }

            $template = '<header ' . $addedHtml . ' class="' . $classes . '" data-uid="' . $uid . '">';

            if ($text and !empty($text->getConfig('supertitle')) and empty($element->getAttribute('data-skipsupertitle'))) {
                $template .= '<p class="supertitle super-title"><span>' . $text->getConfig('supertitle') . '</span></p>';
            }
            elseif (!$text and !empty($element->getAttribute('data-supertitle'))) {
                $template .= '<p class="supertitle super-title"><span>' . $element->getAttribute('data-supertitle') . '</span></p>';
            }

            $subtitle = null;

            if (preg_match('#<span class="subtitle">(.*?)</span>#is', $headlineText, $match)) {

                $headlineText = preg_replace('#<span class="subtitle">(.*?)</span>#is', '', $headlineText);
                $subtitle = $match[1];
            }

            $id = (string) null;

            if ($text and !empty($text->getConfig('elementId'))) {
                $id = ' id="' . $text->getConfig('elementId') . '" ';
            }

            if ($element->getAttribute('id')) {
                $id = ' id="' . $element->getAttribute('id') . '" ';
            }

            if ($element->getAttribute('data-skip-id') !== null) {
                $id = ' data-skip-id ';
            }

            if ($element->getAttribute('data-wrap')) {
                $headlineText = '<span>' . $headlineText . '</span>';
            }

            $template .= '<' . $tagName . ' ' . $id . ' data-editable data-uid="' . $uid . '" style="' . $styles . '">' . $headlineText . '</' . $tagName . '>';

            if ($text and !empty($text->getConfig('subtitle')) and empty($element->getAttribute('data-skipsubline'))) {
                $template .= '<p class="subtitle">' . nl2br($text->getConfig('subtitle')) . '</p>';
            }
            elseif (!$text and !empty($element->getAttribute('data-subtitle'))) {
                $template .= '<p class="subtitle">' . $element->getAttribute('data-subtitle') . '</p>';
            }
            elseif ($subtitle) {
                $template .= '<p class="subtitle">' . nl2br($subtitle) . '</p>';
            }
            elseif (!empty($element->getAttribute('data-subtitle-preset'))) {
                $template .= '<p class="subtitle">' . $element->getAttribute('data-subtitle-preset') . '</p>';
            }

            $template .= '</header>';

            $element->replaceWith($template);
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

            if ($element->getAttribute('data-skip-id') !== null) {
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
