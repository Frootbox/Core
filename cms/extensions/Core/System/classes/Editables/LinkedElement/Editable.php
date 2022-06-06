<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Editables\LinkedElement;

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
    public function parse(
        $html,
        \Frootbox\Db\Db $db,
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): string
    {
        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('[data-editable-link][data-uid]')->each(function ( $element ) use ($texts, $filesRepository, $pagesRepository, $db) {

            $uid = $element->getAttribute('data-uid');

            // Fetch text
            $text = $texts->fetchByUid($uid);

            if ($text) {

                $attribute = ($element->getAttribute('href') !== null) ? 'href' : 'data-href';

                if (!empty($pageId = $text->getConfig('pageId'))) {

                    $page = $pagesRepository->fetchById($pageId);
                    $element->setAttribute($attribute, $page->getUri());
                }
                elseif (!empty($url = $text->getConfig('link'))) {

                    if ($url[0] != '#' and strpos($url, 'http') !== 0) {
                        $url = SERVER_PATH_PROTOCOL . $url;
                    }

                    $element->setAttribute($attribute, $url);
                }
                elseif (!empty($email = $text->getConfig('email'))) {
                    $element->setAttribute($attribute, 'mailto:' . $email);
                }
                elseif (!empty($phone = $text->getConfig('phone'))) {
                    $element->setAttribute($attribute, 'tel:' . $phone);
                }
                elseif (!empty($fileId = $text->getConfig('filelink'))) {

                    $file = $filesRepository->fetchById($fileId);

                    $element->setAttribute($attribute, $file->getUriDownload());
                }
                else {

                }

                if (!empty($text->getConfig('label'))) {

                    $label = $element->filter('.label');

                    if (!empty((string) $label)) {
                        $label->setInnerHtml($text->getConfig('label'));
                    }
                    else {
                        $element->setInnerHtml($text->getConfig('label'));
                    }
                }

                if (!empty($text->getConfig('conversionId'))) {
                    $element->setAttribute('data-conversion', $text->getConfig('conversionId'));
                }
            }
            // Todo: Thats the old way and should be dropped soon
            else {

                // Parse uid
                preg_match('#^([a-z\-]{1,})\:([0-9]{1,})\:(.*?)$#i', $uid, $match);
                $className = str_replace('-', '\\', $match[1]);

                // Fetch target object
                $model = new $className($db);
                $row = $model->fetchById($match[2]);

                $attribute = ($element->getAttribute('href') !== null) ? 'href' : 'data-href';

                if (!empty($url = $row->getConfig('generatedlinks.' . $match[3]))) {

                    if (strpos($url, 'http') !== 0) {
                        $url = SERVER_PATH_PROTOCOL . $url;
                    }

                    $element->setAttribute($attribute, $url);
                }
                elseif (!empty($email = $row->getConfig('generatedlinksEmail.' . $match[3]))) {
                    $element->setAttribute($attribute, 'mailto:' . $email);
                }
                elseif (!empty($phone = $row->getConfig('generatedlinksPhone.' . $match[3]))) {
                    $element->setAttribute($attribute, 'tel:' . $phone);
                }
                elseif (!empty($fileId = $row->getConfig('generatedFilelinks.' . $match[3]))) {

                    $file = $filesRepository->fetchById($fileId);

                    $element->setAttribute($attribute, $file->getUriDownload());
                }
                else {
                    // $element->removeAttribute($attribute);
                }

                if (!empty($row->getConfig('generatedlinkLabels.' . $match[3]))) {
                    $element->setInnerHtml($row->getConfig('generatedlinkLabels.' . $match[3]));
                }
            }
        });

        return $crawler->saveHTML();
    }
}
