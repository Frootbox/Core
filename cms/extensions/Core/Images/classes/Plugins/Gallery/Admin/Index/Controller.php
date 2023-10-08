<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\Gallery\Admin\Index;

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
    public function ajaxUpdateAction (
        \Frootbox\Http\Post $post
    ): Response
    {
        d($post);
    }

    /**
     * 
     */
    public function ajaxCategoryDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
    ): Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));
        $view->set('category', $category);
        
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxSortByFilenameAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Files $filesRepository,
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
    ): Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        // Fetch files
        $files = $filesRepository->fetch([
            'where' => [
                'uid' => $category->getUid('images'),
            ],
            'order' => [ 'name ASC' ],
        ]);

        $orderId = $files->getCount() + 1;

        foreach ($files as $file) {
            $file->setOrderId(--$orderId);
            $file->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
