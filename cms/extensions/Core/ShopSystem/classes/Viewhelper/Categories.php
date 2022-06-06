<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Viewhelper;

class Categories extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getCategory' => [
            'categoryId'
        ]
    ];

    /**
     *
     */
    public function getCategoryAction(
        $categoryId,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    )
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($categoryId);

        return $category;
    }

    /**
     * 
     */
    public function getTopCategoriesAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch top categories
        $result = $categoriesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\MatchColumn('parentId', 'rootId'),
                'visibility' => 2
            ],
            'order' => [ 'lft ASC' ]
        ]);

        return $result;
    }
}
