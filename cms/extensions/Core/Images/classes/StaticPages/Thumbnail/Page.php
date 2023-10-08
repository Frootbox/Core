<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Images\StaticPages\Thumbnail;

class Page
{
    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Config\Config $config
     * @param \Frootbox\Persistence\Repositories\Files $files
     * @return void
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function render(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
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
        $thumbnailClass = $config->get('thumbnails.customThumbnailClass') ?? \Frootbox\Thumbnail::class;


        $thumbnail = new $thumbnailClass([
            'path' => $file->getPath(),
            'width' => $get->get('width'),
            'height' => $get->get('height'),
            'crop' => $get->get('crop'),
            'rotation' => $file->getRotation(),
        ], $config->get('thumbnails'));


        if (empty($get->get('path')) and empty($file->getConfig('detectedOrientation'))) {

            $orientation = $thumbnail->detectOrientation();

            switch ($orientation) {
                case 'LeftBottom':
                    $rotation = 270;
                    break;

                case 'BottomRight':
                    $rotation = 180;
                    break;

                case 'RightTop':
                    $rotation = 90;
                    break;

                default:
                    $rotation = null;
            }

            $file->addConfig([
                'detectedOrientation' => $orientation,
                'suggestRotation' => $rotation,
            ]);

            $file->save();

            if ($rotation !== null) {
                $thumbnail->setRotation($rotation);
            }
        }


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
