<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Navigations\Items\Category;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('categoryId'))) {
            return '#unconfigured-navigation-item';
        }

        $categoriesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $category = $categoriesRepository->fetchById($this->getConfig('categoryId'));

        return $category->getUri();

        $url = 'fbx://page:' . $this->getConfig('categoryId');

        if (!empty($this->getConfig('anchor'))) {
            $url .= '#' . $this->getConfig('anchor');
        }

        return $url;
    }

    /**
     *
     */
    public function getCategories(): \Frootbox\Db\Result
    {
        // Fetch categories
        $categoriesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $categories = $categoriesRepository->fetch();

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
     *
     */
    public function initialize(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): void
    {
        // Fetch matching pages
        $result = $categoriesRepository->fetch([
            'where' => [
                'title' => $this->getTitle(),
            ],
        ]);

        if ($result->getCount() == 1) {

            $category = $result->current();

            $this->addConfig([
                'categoryId' => $category->getId(),
            ]);
            $this->save();
        }
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'categoryId' => $post->get('categoryId'),
        ]);
    }
}
