<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Admin\Recipe;

use Frootbox\Admin\Controller\Response;
use Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxImportAction(
        \Frootbox\Config\Config $config,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    )
    {
        exit;
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository
    ): Response
    {
        // Fetch recipes
        $recipes = $recipesRepository->fetch([
            'order' => [ 'title ASC']
        ]);

        return self::getResponse('plain', 200, [
            'recipes' => $recipes
        ]);
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        if (!empty($post->get('recipeId'))) {

            // Fetch recipe
            $recipe = $recipesRepository->fetchById($post->get('recipeId'));
        }
        elseif (!empty($post->get('title'))) {

            // Insert new recipe
            $recipe = $recipesRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Recipe([
                'pluginId' => $this->plugin->getId(),
                'pageId' => $this->plugin->getPage()->getId(),
                'title' => $post->get('title')
            ]));
        }
        else {
            throw new \Frootbox\Exceptions\InputInvalid('Missing input.');
        }

        $category->connectItem($recipe);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#listEntriesCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Admin\Categories\Partials\ListRecipes\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin,
                    'highlight' => $recipe->getId(),
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository
    ): Response
    {
        // Fetch recipe
        $recipe = $recipesRepository->fetchById($get->get('recipeId'));

        $recipe->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => 'tr[data-recipe="' . $recipe->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxUnlinkAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        // Fetch recipe
        $recipe = $recipesRepository->fetchById($get->get('recipeId'));

        $category->disconnectItem($recipe);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#listEntriesCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Admin\Categories\Partials\ListRecipes\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        d($post);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository
    ): Response
    {
        // Fetch recipes
        $recipes = $recipesRepository->fetch([
            'order' => [ 'title ASC' ]
        ]);

        return self::getResponse('html', 200, [
            'recipes' => $recipes
        ]);
    }
}
