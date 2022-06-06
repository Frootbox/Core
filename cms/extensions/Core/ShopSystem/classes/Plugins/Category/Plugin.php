<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Category;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function addProductAction(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
    ): Response
    {

    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        if (empty($this->getConfig('categoryId'))) {
            return new \Frootbox\View\Response;
        }

        // Fetch category
        $category = $categoriesRepository->fetchById($this->getConfig('categoryId'));

        return new \Frootbox\View\Response([
            'category' => $category,
        ]);
    }
}
