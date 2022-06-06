<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\StaticPages\Dummy;

class Page
{
    /**
     * 
     */
    public function render(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\ConfigStatics $configStatics,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        $params = [
            'uid' => $get->get('uid'),
            'width' => (!empty($get->get('width')) ? (int) $get->get('width') : null),
            'height' => (!empty($get->get('height')) ? (int) $get->get('height') : null),
        ];

        $key = md5(serialize($params));
        $cachefile = FILES_DIR . 'cache/public/' . $configStatics->getCacheRevision() . '/dummy/image-' . $key . '.jpg';

        if (file_exists($cachefile)) {
            header('Content-type: image/jpg');
            readfile($cachefile);
            exit;
        }

        if (!file_exists(dirname($cachefile))) {
            $dir = new \Frootbox\Filesystem\Directory(dirname($cachefile));
            $dir->make();
        }

        if (!empty($get->get('uid'))) {

            $file = $files->fetchOne([
                'where' => [
                    'uid' => $get->get('uid'),
                ],
            ]);

            if (!empty($file)) {

                $thumbnail = new \Frootbox\Thumbnail([
                    'path' => $file->getPath(),
                    'width' => $get->get('width'),
                    'height' => $get->get('height'),
                ], $config->get('thumbnails'));

                $thumbnail->render();

                copy($thumbnail->getCacheFile(), $cachefile);

                // Serve image
                header('Content-type: image/jpg');
                readfile($thumbnail->getCacheFile());
                exit;
            }
        }

        // TODO validate get data by payload class
        $width = $get->get('width') ?? 1024;
        $height = $get->get('height') ?? 200;

        if (empty((int) $width)) {
            $width = 1024;
        }

        if (empty((int) $height)) {
            $height = 1024;
        }


        if (!file_exists($cachefile)) {

            $image = \imagecreate($width, $height);
            imagecolorallocate($image, 203, 203, 203);

            if (!empty($get->get('width') or !empty($get->get('height')))) {

                $white = imagecolorallocate($image, 255, 255, 255);

                $fontFile = __DIR__ . '/resources/private/fonts/YanoneKaffeesatz-Regular.ttf';

                if (!empty($get->get('width') and !empty($get->get('height')))) {
                    $text = $width . ' x ' . $height . '';
                }
                elseif (!empty($get->get('width'))) {
                    $text = 'Breite ' . $get->get('width');
                }
                elseif (!empty($get->get('height'))) {
                    $text = 'Höhe ' . $get->get('height');
                }

                if (function_exists('imagettfbbox')) {

                    for ($size = 64; $size > 0; --$size) {

                        $bbox = imagettfbbox($size, 0, $fontFile, $text);

                        if ($width > ($bbox[2] + 40)) {
                            break;
                        }
                    }

                    $text_width = $bbox[2] - $bbox[0];
                    $text_height = $bbox[7] - $bbox[1];

                    $x = ($width / 2) - ($text_width / 2);
                    $y = ($height / 2) - ($text_height / 2);

                    imagettftext($image, $size, 0, $x, $y, $white, $fontFile, $text);
                }
            }

            imagejpeg($image, $cachefile);
        }

        header('Content-Type: image/png');
        readfile($cachefile);
        exit;
    }
}
