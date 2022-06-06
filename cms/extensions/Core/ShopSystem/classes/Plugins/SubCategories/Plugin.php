<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\SubCategories;

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
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($this->getConfig('categoryId'));

        $view->set('category', $category);
    }
}
