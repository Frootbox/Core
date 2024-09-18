<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence;

class CategoryConnection extends \Frootbox\Persistence\CategoryConnection
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Uid;

    protected $model = Repositories\CategoriesConnections::class;

    /**
     * {@inheritdoc}
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        // Fetch category
        $categoriesRepository = $this->db->getModel(\Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories::class);
        $category = $categoriesRepository->fetchById($this->getCategoryId());

        // Fetch recipe
        $productsRepository = $this->db->getModel(\Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes::class);
        $product = $productsRepository->fetchById($this->getItemId());

        // Build virtual directory
        $trace = $category->getTrace();
        $trace->shift();

        $virtualDirectory = [ ];

        foreach ($trace as $child) {
            $virtualDirectory[] = $child->getTitle();
        }

        $virtualDirectory[] = $product->getTitle();

        return new \Frootbox\Persistence\Alias([
            'pageId' => $product->getPageId(),
            'virtualDirectory' => $virtualDirectory,
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showRecipe',
                'recipeId' => $product->getId(),
                'categoryId' => $category->getId()
            ])
        ]);
    }
}