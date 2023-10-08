<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Editables\PhysicalImage\Admin;

use \Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Ext\Core\Editing\Editables\AbstractController
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
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        return self::getResponse('html', 200, [
            'file' => $file
        ]);
    }

    /**
     *
     */
    public function ajaxRotate(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        $rotation = (int) $file->getConfig('rotation');

        $rotation += ($get->get('direction') == 'left' ? -90 : 90);

        $file->addConfig([
            'rotation' => $rotation
        ]);

        $file->save();

        return self::getResponse('json', 200, [
            'src' => $file->getUriThumbnail([ 'height' => $get->get('height'), 'width' => $get->get('width'), ]),
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Persistence\Repositories\Files $files
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $files
    ): Response
    {
        // Fetch file
        $file = $files->fetchById($get->get('fileId'));

        // Update file
        $file->setTitle($post->get('title'));
        $file->setCopyright($post->get('copyright'));

        $file->addConfig([
            'caption' => $post->get('caption'),
        ]);

        if ($post->hasAttribute('link')) {
            $file->addConfig([
                'link' => $post->get('link'),
            ]);
        }

        $file->save();

        return self::getResponse('json', 200, [
            'src' => 'dsfsdfdsfsf'
        ]);
    }
}
