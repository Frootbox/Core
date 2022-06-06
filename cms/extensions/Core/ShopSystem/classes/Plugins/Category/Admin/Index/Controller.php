<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Category\Admin\Index;

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
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Set new config
        $this->plugin->addConfig([
            'categoryId' => $post->get('categoryId')
        ]);
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch categories
        $categories = $categoriesRepository->fetch();

        return self::getResponse('html', 200, [
            'categories' => $categories
        ]);
    }
}
