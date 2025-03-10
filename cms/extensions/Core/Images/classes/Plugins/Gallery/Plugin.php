<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\Gallery;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showCategory'
    ];

    /**
     * Get plugins root path
     */
    public function getPath ( ) : string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
    

    /**
     *
     */
    public function getRootCategory (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories
    )
    {
        // Fetch top categories
        $result = $categories->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId')
            ],
            'limit' => 1
        ]);

        return $result->current();
    }

    /**
     * @param int|null $limit
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getTopCategories(
        int $limit = null,
    ): \Frootbox\Db\Result
    {
        // Fetch top categories
        $categories = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Persistence\Repositories\Categories::class);
        $result = $categories->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('parentId', 'rootId'),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', 1)
            ],
            'order' => [ 'lft ASC' ],
            'limit' => $limit ?? 1024,
        ]);
        
        return $result;
    }
    
    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categoriesRepository
    ): void
    {
        // Fetch categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => [ 'lft DESC' ]
        ]);

        $result->map('delete');
    }

    /**
     *
     */
    public function indexAction (
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {

    }
    
    /**
     *
     */
    public function showCategoryAction (
        \Frootbox\Ext\Core\Images\Persistence\Repositories\Categories $categories,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {        
        // Fetch category
        $category = $categories->fetchById($this->getAttribute('categoryId'));
        $view->set('category', $category);
    }
}
