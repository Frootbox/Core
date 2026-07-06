<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\System\Editables\SimpleElement;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{
    /**
     * @return string
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

            if (!empty($text->getConfig('elementId'))) {
                $element->setAttribute('id', $text->getConfig('elementId'));
            }

            $element->setInnerHtml(nl2br($text->getText()));
        });

        return $crawler->saveHTML();
    }
}