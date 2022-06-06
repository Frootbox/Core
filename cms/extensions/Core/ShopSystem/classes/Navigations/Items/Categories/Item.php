<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Navigations\Items\Categories;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    protected $hasAutoItems = true;

    /**
     *
     */
    public function getAutoItems(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): array
    {
        $categories = $categoriesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'parentId'),
            ],
            'order' => [ 'lft ASC' ],
        ]);

        $list = [];

        foreach ($categories as $category) {

            $item = new \Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy([
                'title' => $category->getTitle(),
                'href' => $category->getUri(),
            ]);

            $list[] = $item;
        }

        return $list;
    }

    /**
     *
     */
    public function getHref(): string
    {

    }

    /**
     *
     */
    public function getCategories(): \Frootbox\Db\Result
    {

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
        ]);
    }
}
