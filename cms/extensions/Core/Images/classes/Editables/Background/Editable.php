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
        $crawler->filter('[data-type="Images/Background"][data-uid]')->each(function ( $element ) use ($files, &$injectedHtml) {

            $uid = $element->getAttribute('data-uid');

            // Fetch file
            $file = $files->fetchByUid($uid, [
                'fallbackLanguageDefault' => true,
                'order' => 'orderId DESC',
            ]);

            if (!$file and $element->getAttribute('data-fallback-uid') !== null) {

                $file = $files->fetchByUid($element->getAttribute('data-fallback-uid'), [
                    'fallbackLanguageDefault' => true,
                    'order' => 'orderId DESC',
                ]);

            }


            if ($file and ($element->getAttribute('data-physicalwidth') !== null or $element->getAttribute('data-physicalheight') !== null)) {

                if (file_exists(FILES_DIR . $file->getPath())) {

                    $data = getimagesize(FILES_DIR . $file->getPath());

                    if (!empty($data)) {
                        $element->setAttribute('data-physicalwidth', $data[0]);
                        $element->setAttribute('data-physicalheight', $data[1]);
                    }
                }
            }

            $src = (string) null;
            $style = $element->getAttribute('style');

            // $style = (string) null;
            // $classId = 'background-loader-' . rand(10000, 99999);


            if ($file) {

                $element->setAttribute('role', 'img');
                $element->setAttribute('aria-label', $file->getAlt());


                if (!empty($file->getConfig('backgroundColor'))) {
                    $style .= ' background: ' . $file->getConfig('backgroundColor') . ';';
                }
                else {

                    $width = $element->getAttribute('data-width') ?? null;
                    $height = $element->getAttribute('data-height') ?? null;

                    $src = $file->getUri([ 'width' => $width, 'height' => $height ]);
                }

                if (!empty($file->getCopyright())) {
                    $element->setAttribute('data-copyright', $file->getCopyright());
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


            // Fetch file
            $file = $files->fetchByUid($uid . '-desktop', [
                'fallbackLanguageDefault' => true,
                'order' => 'orderId DESC',
            ]);

            if ($file) {

                if ($file and ($element->getAttribute('data-physicalwidth-md') !== null or $element->getAttribute('data-physicalheight-md') !== null)) {

                    if (file_exists(FILES_DIR . $file->getPath())) {

                        $data = getimagesize(FILES_DIR . $file->getPath());

                        if (!empty($data)) {
                            $element->setAttribute('data-physicalwidth-md', $data[0]);
                            $element->setAttribute('data-physicalheight-md', $data[1]);
                        }
                    }
                }

                $customClass = 'bg-file-' . $file->getId();

                $classes = $element->getAttribute('class');
                $classes .= ' ' . $customClass;

                $element->setAttribute('class', $classes);

                $width = $element->getAttribute('data-width-xl') ?? null;
                $height = $element->getAttribute('data-height-xl') ?? null;

                $src = $file->getUri([ 'width' => $width, 'height' => $height ]);

                $injectedHtml .= '<style>
                    @media (min-width: 768px) {
                        .' . $customClass . ' {
                            background-image: url(' . $src . ') !important;
                        }
                    }
                </style>';
            }
        });


        $html = $crawler->saveHTML();

        $html = str_replace('</head>', $injectedHtml . '</head>', $html);

        return $html;
    }
}
