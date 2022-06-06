<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\SimpleElement;

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
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): string
    {
        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('[data-editable-element][data-uid]')->each(function ( $element ) use ($texts) {

            $uid = $element->getAttribute('data-uid');

            // Fetch file
            $text = $texts->fetchByUid($uid, [
                'fallbackLanguageDefault' => true,
            ]);

            // d($text);
            if (empty($text)) {
                return;
            }

            $element->setInnerHtml(nl2br($text->getText()));
        });

        return $crawler->saveHTML();
    }
}