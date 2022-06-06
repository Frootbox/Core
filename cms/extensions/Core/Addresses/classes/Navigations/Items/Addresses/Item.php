<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Navigations\Items\Addresses;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    protected $hasAutoItems = true;

    /**
     *
     */
    public function getAutoItems(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): array
    {
        d($addressesRepository);
        if (empty($this->getConfig('pageId'))) {
            return [];
        }

        $pages = $pagesRepository->fetch([
            'where' => [
                'parentId' => $this->getConfig('pageId'),
            ],
            'order' => [ 'lft ASC' ],
        ]);

        $list = [];

        foreach ($pages as $page) {

            $item = new \Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy([
                'title' => $page->getTitle(),
                'href' => $page->getUri(),
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
    public function initialize(): void
    {

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
