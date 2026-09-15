<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
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
            'focusPoint' => $file->getConfig('focusPoint') ?? null,
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

            $width = $get->get('width') ?? 300;
            $height = $get->get('height') ?? 300;

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

        // Open before sending headers: generation may fail or the cache may be cleared.
        $cacheFile = $thumbnail->getCacheFile();
        $stream = @fopen($cacheFile, 'rb');

        if ($stream === false) {
            error_log('Thumbnail output could not be opened: ' . $cacheFile);
            http_response_code(500);
            exit;
        }

        // Convert supported raster images in memory, avoiding partial WebP cache files.
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp') !== false
            && !empty($config->get('thumbnails.webp'))
            && function_exists('imagewebp')
            && function_exists('imagecreatefromstring')
            && in_array(strtolower(pathinfo($cacheFile, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true)) {

            $imageData = stream_get_contents($stream);
            $img = $imageData !== false ? @imagecreatefromstring($imageData) : false;

            if ($img !== false) {
                ob_start();
                $converted = @imagewebp($img, null, 80);
                $webp = ob_get_clean();
                imagedestroy($img);

                if ($converted && $webp !== false && $webp !== '') {
                    fclose($stream);
                    header('Vary: Accept', false);
                    header('Content-Type: image/webp');
                    echo $webp;
                    exit;
                }
            }

            rewind($stream);
        }

        header('Vary: Accept', false);
        header('Content-Type: ' . $file->getType());
        fpassthru($stream);
        fclose($stream);
        exit;
    }
}
