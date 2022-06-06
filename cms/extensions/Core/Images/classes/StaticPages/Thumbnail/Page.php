<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\StaticPages\Thumbnail;

class Page
{
    /**
     * 
     */
    public function render(
        \Frootbox\Config\Config $config,
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        if (!empty($get->get('path'))) {
            $file = new \Frootbox\Persistence\File([
                'path' => $get->get('path'),
            ]);
        }
        else {
            // Fetch file
            $file = $files->fetchById($get->get('fileId'));
        }

        // Generate thumbnail
        $thumbnail = new \Frootbox\Thumbnail([
            'path' => $file->getPath(),
            'width' => $get->get('width'),
            'height' => $get->get('height'),
            'crop' => $get->get('crop'),
            'rotation' => $file->getConfig('rotation')
        ], $config->get('thumbnails'));

        if (!$thumbnail->exists()) {

            $width = $get->get('width');
            $height = $get->get('height');

            $image = imagecreate($width, $height);
            imagecolorallocate($image, 255, 255, 255);

            $grey = imagecolorallocate($image, 203, 203, 203);

            for ($y = 0; $y < $height; $y += 8) {

                for ($x = 0; $x < $width; $x += 16) {

                    $nx = ($y % 16 == 0) ? $x : $x - 8;
                    imagefilledrectangle($image, $nx, $y, $nx + 8, $y + 8, $grey);
                }
            }

            header('Content-Type: image/png');
            imagepng($image);
            exit;
        }

        $thumbnail->render();

        // Serve image
        if (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false and !empty($config->get('thumbnails.webp'))) {

            if (function_exists('imagewebp')) {

                $img = imagecreatefromjpeg($thumbnail->getCacheFile());
                imagewebp($img, $thumbnail->getCacheFile() . '.webp', 80);
                imagedestroy($img);
            }

            header('Content-type: image/webp');
            readfile($thumbnail->getCacheFile() . '.webp');
            exit;
        }
        else {
            header('Content-type: ' . $file->getType());
        }

        readfile($thumbnail->getCacheFile());
        exit;
    }
}
