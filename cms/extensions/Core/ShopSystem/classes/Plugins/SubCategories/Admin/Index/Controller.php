<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\SubCategories\Admin\Index;

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
    ): \Frootbox\Admin\Controller\Response
    {
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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch categories
        $result = $categoriesRepository->fetch([

        ]);

        $view->set('categories', $result);


        return self::getResponse();
    }
}
