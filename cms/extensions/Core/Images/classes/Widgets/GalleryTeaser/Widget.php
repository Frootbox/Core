<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Widgets\GalleryTeaser;

class Widget extends \Frootbox\Persistence\Content\AbstractWidget
{
    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories
    ): ?\Frootbox\Ext\Core\Images\Persistence\Category
    {
        if (empty($this->getConfig('categoryId'))) {
            return null;
        }

        // Fetch category
        $category = $categories->fetchById($this->getConfig('categoryId'));

        return $category;
    }

    /**
     * Get widgets root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * Cleanup widgets resources before it gets deleted
     *
     * Dependencies get auto injected.
     */
    public function unload(
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {

    }
}