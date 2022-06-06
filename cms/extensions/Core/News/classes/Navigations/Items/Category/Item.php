<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Navigations\Items\Category;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('categoryId'))) {
            return '#';
        }

        // Fetch category
        $categoriesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\News\Persistence\Repositories\Categories::class);
        $category = $categoriesRepository->fetchById($this->getConfig('categoryId'));

        return $category->getUri();
    }

    /**
     *
     */
    public function getCategories(): \Frootbox\Db\Result
    {
        // Fetch categories
        $categoriesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\News\Persistence\Repositories\Categories::class);
        $categories = $categoriesRepository->fetch([
            'order' => [
                'lft ASC',
            ],
        ]);

        return $categories;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'categoryId' => $post->get('categoryId'),
            'showChildren' => $post->get('showChildren'),
        ]);
    }
}
