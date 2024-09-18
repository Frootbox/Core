<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Admin\Index;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::response('plain');
    }

    public function ajaxModalImportAction(

    ): Response
    {
        return self::response('plain');
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

    public function exportAction(
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        function escapeJsonString($value) {
            # list from www.json.org: (\b backspace, \f formfeed)
            $escapers =     array("\\",     "/",   "\"",  "\n",  "\r",  "\t", "\x08", "\x0c");
            $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t",  "\\f",  "\\b");
            $result = str_replace($escapers, $replacements, $value);
            return $result;
        }

        $exportData = [
            'recipes' => [],
            'categories' => [],
        ];

        // Fetch recipes
        $result = $recipesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $recipe) {

            $ingredients = $texts->fetchByUid($recipe->getUid('ingredients'));
            $preparation = $texts->fetchByUid($recipe->getUid('preparation'));

            $recipeData = $recipe->getData();
            $recipeData['ingredients'] = ($ingredients?->getText());
            $recipeData['preparation'] = ($preparation?->getText());

            $exportData['recipes'][$recipe->getId()] = $recipeData;
        }


        // Fetch categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $category) {

            if ($category->getTitle() == 'Index') {
                continue;
            }

            $exportData['categories'][] = $category->getData();

            foreach ($category->getItems() as $recipe) {
                $exportData['recipes'][$recipe->getId()]['categories'][] = $category->getId();
            }
        }

        header("Content-Disposition: attachment; filename=export.json");
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode($exportData, JSON_UNESCAPED_SLASHES));
    }

    /**
     *
     */
    public function importAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Recipes $recipesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        $importData = json_decode($post->get('Json'), true);

        $connections = [
            'categories' => [],
        ];

        // Clean categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'lft DESC' ],
        ]);

        $result->map('delete');

        // Clean recipes
        $result = $recipesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $result->map('delete');

        // Create root
        $root = $categoriesRepository->insertRoot(new \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Category([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'title' => 'Index',
            'uid' => $this->plugin->getUid('categories'),
        ]));

        foreach ($importData['categories'] as $categoryData) {

            $oldId = $categoryData['id'];

            // Clean data
            unset($categoryData['id'], $categoryData['lft'], $categoryData['rgt'], $categoryData['alias']);

            // Compose category
            $categoryData['pluginId'] = $this->plugin->getId();
            $categoryData['pageId'] = $this->plugin->getPageId();
            $categoryData['uid'] = $this->plugin->getUid('categories');

            $newCategory = new \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Category($categoryData);

            $root->appendChild($newCategory);

            $connections['categories'][$oldId] = $newCategory;
        }


        foreach ($importData['recipes'] as $recipeData) {

            if (empty($recipeData['categories'])) {
                continue;
            }

            $oldId = $recipeData['id'];
            $categories = $recipeData['categories'];

            $textIngredients = $recipeData['ingredients'];
            $textIngredients = str_replace('\n', '', $textIngredients);

            $textPreparation = $recipeData['preparation'];
            $textPreparation = str_replace('\n', '', $textPreparation);


            // Clean data
            unset($categoryData['id'], $categoryData['alias'], $recipeData['ingredients'], $recipeData['preparation'], $recipeData['categories']);

            // Compose recipe
            $recipeData['pluginId'] = $this->plugin->getId();
            $recipeData['pageId'] = $this->plugin->getPageId();

            // Persist recipe
            $newRecipe = new \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Recipe($recipeData);
            $recipesRepository->persist($newRecipe);

            // Persist texts
            $text = new \Frootbox\Persistence\Content\Text([
                'userId' => USER_ID,
                'uid' => $newRecipe->getUid('ingredients'),
                'text' => $textIngredients,
            ]);

            $texts->persist($text);

            $text = new \Frootbox\Persistence\Content\Text([
                'userId' => USER_ID,
                'uid' => $newRecipe->getUid('preparation'),
                'text' => $textPreparation,
            ]);

            $texts->persist($text);


            foreach ($categories as $categoryId) {

                $category = $connections['categories'][$categoryId];
                $category->connectItem($newRecipe);
            }
        }


        return self::getResponse();
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }
}
