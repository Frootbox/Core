<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\PictureFull;

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
        $crawler->filter('figure[data-type="Images/PictureFull"][data-uid]')->each(function ( $element ) use ($files) {

            // Extract uid
            $uid = $element->getAttribute('data-uid');

            // Extract default image source
            preg_match('#src="(.*?)"#', (string) $element, $matches);
            $default = $matches[1];

            // Fetch file
            $file = $files->fetchByUid($uid, [
                'fallbackLanguageDefault' => true,
            ]);

            if (!$file) {

                if (!defined('EDITING')) {
                    $element->setInnerHtml(' ');
                    $element->remove();

                    return;
                }

                $src = $element->filter('img')->getAttribute('src');
                $bigsrc = (string) null;

                $alt = 'Dummy';
            }
            else {

                $src = $file->getUri([ 'width' => 1024 ]);
                $bigsrc = $file->getUri([ 'width' => 1200 ]);
            }

            if (preg_match('#width="(.*?)"#', (string) $element, $match)) {
                $width = $match[1];
            }
            else {
                $width = 1024;
            }

            if ($file) {
                $alt = !empty($file->getConfig('caption')) ? strip_tags($file->getConfig('caption')) : $file->getName();
            }

            $html = '<picture class="fluid">
                            <img data-default="' . $default . '" width="' . $width . '" src="' . $src . '" alt="' . $alt . '" />
                        </picture>';

            if ($file and $file->getConfig('magnifier')) {
                $html = '<a href="' . $bigsrc . '" data-fancybox="gallery">' . $html . '</a>';
            }

            if ($file and (!empty($file->getConfig('caption')) or !empty($file->getCopyright()))) {
                $html .= '        
                        <figcaption>
                            ' . (($file and $file->getCopyright()) ? '<span class="copyright">' . $file->getCopyright() . '</span>' : '') . '
                            ' . ($file ? nl2br($file->getConfig('caption')) : null) . '                            
                        </figcaption>';
            }

            $element->setInnerHtml($html);
        });

        $html = $crawler->saveHTML();

        return $html;
    }
}