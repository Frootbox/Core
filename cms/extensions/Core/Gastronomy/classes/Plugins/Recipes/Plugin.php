<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
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
    public function getRootCategory(
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Persistence\Category
    {
        // Fetch root category
        $category = $categoriesRepository->fetchOne([
            'where' => [
                'uid' => $this->getUid('categories'),
            ],
        ]);

        return $category;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new \Frootbox\View\Response([

        ]);
    }

    /**
     *
     */
    public function showCategoryAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($this->getAttribute('categoryId'));

        return new \Frootbox\View\Response([
            'category' => $category
        ]);
    }

    /**
     *
     */
    public function showRecipeAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($this->getAttribute('categoryId'));

        // Fetch recipe
        $recipe = $recipesRepository->fetchById($this->getAttribute('recipeId'));

        return new \Frootbox\View\Response([
            'category' => $category,
            'recipe' => $recipe
        ]);
    }
}
