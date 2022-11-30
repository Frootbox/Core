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

            $uid = $element->getAttribute('data-uid');

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
                            <img width="' . $width . '" src="' . $src . '" alt="' . $alt . '" />
                        </picture>';

            if ($file->getConfig('magnifier')) {
                $html = '<a href="' . $bigsrc . '" data-fancybox="gallery">' . $html . '</a>';
            }

            $html .= '        
                    <figcaption>
                        ' . ($file ? nl2br($file->getConfig('caption')) : null) . '
                        ' . (($file and $file->getCopyright()) ? '<span class="copyright">' . $file->getCopyright() . '</span>' : '') . '
                    </figcaption>';

            $element->setInnerHtml($html);
        });

        $html = $crawler->saveHTML();

        return $html;
    }
}