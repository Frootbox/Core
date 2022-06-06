<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\Gallery\Admin\Import;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxImportAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch target category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch source category
        $source = $categoriesRepository->fetchById($post->get('sourceId'));

        $newUid = $category->getUid('images');

        foreach ($source->getImages($files) as $file) {

            $file->setUid($newUid);
            $file->save($file);
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch categories
        $result = $categoriesRepository->fetch();

        return self::getResponse('plain', 200, [
            'categories' => $result,
        ]);
    }
}
