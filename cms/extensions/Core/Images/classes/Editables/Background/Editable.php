<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\Background;

class Editable extends \Frootbox\AbstractEditable implements \Frootbox\Ext\Core\System\Editables\EditableInterface
{

    /**
     *
     */
    public function getPath ( ): string
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
        $injectedHtml = (string ) null;

        // Replace picture tags
        $crawler = \Wa72\HtmlPageDom\HtmlPageCrawler::create($html);
        $crawler->filter('[data-type="Images/Background"][data-uid]')->each(function ( $element ) use ($files, $injectedHtml) {

            $uid = $element->getAttribute('data-uid');

            // Fetch file
            $file = $files->fetchByUid($uid, [
                'fallbackLanguageDefault' => true,
                'order' => 'orderId DESC',
            ]);

            if ($file and ($element->getAttribute('data-physicalwidth') !== null or $element->getAttribute('data-physicalheight') !== null)) {

                if (file_exists(FILES_DIR . $file->getPath())) {
                    $data = getimagesize(FILES_DIR . $file->getPath());
                    $element->setAttribute('data-physicalwidth', $data[0]);
                    $element->setAttribute('data-physicalheight', $data[1]);
                }
            }

            $src = (string) null;
            $style = $element->getAttribute('style');

            // $style = (string) null;
            // $classId = 'background-loader-' . rand(10000, 99999);


            if ($file) {

                if (!empty($file->getConfig('backgroundColor'))) {
                    $style .= ' background: ' . $file->getConfig('backgroundColor') . ';';
                }
                else {

                    $width = $element->getAttribute('data-width') ?? null;
                    $height = $element->getAttribute('data-height') ?? null;

                    $src = $file->getUri([ 'width' => $width, 'height' => $height ]);
                }
            }
            elseif (!empty($element->getAttribute('data-default'))) {
                $src = $element->getAttribute('data-default');
            }
            else {
                $element->addClass('no-background');
            }

            /*
            if (!empty($element->getAttribute('data-height'))) {
                $style .= 'height: ' . $element->getAttribute('data-height') . 'px; ';
            }
            */

            if (!empty($src)) {
                $style .= 'background-image: url(' . $src . '); background-repeat: no-repeat; background-size: cover; background-position: center';
            }

            $element->setAttribute('style', $style);
        });

        $html = $crawler->saveHTML();

        return $html;
    }
}
