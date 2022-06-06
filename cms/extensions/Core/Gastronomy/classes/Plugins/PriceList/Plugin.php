<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getAdditives(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch additives
        $result = $additivesRepository->fetch([
            'where' => [ 'pluginId' => $this->getId() ],
            'order' => [ 'orderId ASC' ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoriesRepository
    ): ?\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Category
    {
        // Fetch root category
        $category = $categoriesRepository->fetchOne([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId')
            ]
        ]);

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
    public function getTopCategories(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch top categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
                new \Frootbox\Db\Conditions\MatchColumn('parentId', 'rootId')
            ]
        ]);

        return $result;
    }
}
