<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\GalleryTeaser;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categoriesRepository
    ): ?\Frootbox\Ext\Core\Images\Persistence\Category
    {
        if (empty($this->config['categoryId'])) {
            return null;
        }

        $category = $categoriesRepository->fetchById($this->config['categoryId']);

        return $category;
    }

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
        \Frootbox\View\Engines\Interfaces\Engine $view
    ) {

    }
}
