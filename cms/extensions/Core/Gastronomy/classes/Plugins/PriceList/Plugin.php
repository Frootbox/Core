<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    protected $publicActions = [
        'index',
        'showCategory'
    ];

    /**
     *
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor,
    ): void
    {
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        $idsTable = [
            'categories' => [
                0 => 0
            ],
            'additives' => [

            ],
        ];

        $categoryRepository = $container->get(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories::class);
        $additiveRepository = $container->get(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives::class);

        // Clone additives
        $additives = $additiveRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ],
        ]);

        foreach ($additives as $additive) {

            $newAdditive = $additive->duplicate();
            $newAdditive->setPluginId($this->getId());
            $newAdditive->setPageId($this->getPage()->getId());

            $idsTable['additives'][$additive->getId()] = $newAdditive->getId();
        }

        // Clone categories
        $baseCategory = $categoryRepository->fetchOne([
            'where' => [
                'uid' => $ancestor->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        $tree = $categoryRepository->getTree($baseCategory->getId());

        foreach ($tree as $category) {

            $category->unset([ 'level', 'offspring' ]);

            $newCategory = $category->duplicate();

            $idsTable['categories'][$category->getId()] = $newCategory->getId();

            $newCategory->setPluginId($this->getId());
            $newCategory->setPageId($this->getPage()->getId());
            $newCategory->setUid($this->getUid('categories'));
            $newCategory->setRootId($idsTable['categories'][$category->getRootId()]);
            $newCategory->setParentId($idsTable['categories'][$category->getParentId()]);
            $newCategory->setAlias(null);
            $newCategory->save();

            $cloningMachine->cloneContentsForElement($newCategory, $category->getUidBase());

            // Clone positions
            $positions = $category->getPositions();

            foreach ($positions as $position) {

                $position->unset([ 'connConfig', 'connId' ]);

                $newPosition = $position->duplicate();
                $newPosition->setPluginId($this->getId());
                $newPosition->setPageId($this->getPage()->getId());

                $cloningMachine->cloneContentsForElement($newPosition, $position->getUidBase());

                $newCategory->connectItem($newPosition);

                // Clone prices
                foreach ($position->getPrices() as $price) {

                    $newPrice = $price->duplicate();
                    $newPrice->setPluginId($this->getId());
                    $newPrice->setPageId($this->getPage()->getId());

                    $newPrice->setParentId($newPosition->getId());
                    $newPrice->save();

                    // Clone additives
                    if (!empty($newPrice->getConfig('additives'))) {

                        $additives = $newPrice->getConfig('additives');

                        $newPrice->unsetConfig('additives');

                        $newAdditives = [];

                        foreach ($additives as $id) {
                            $newAdditives[] = $idsTable['additives'][$id];
                        }

                        $newPrice->addConfig([
                            'additives' => $newAdditives,
                        ]);
                        $newPrice->save();
                    }
                }
            }
        }
    }


    /**
     * Cleanup before deleting plugin
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additiveRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoryRepository,
    ): void
    {
        // Clean up additives
        $additives = $additiveRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $additives->map('delete');

        // Clean up categories
        $categories = $categoryRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
            'order' => [ 'lft DESC' ],
        ]);

        $categories->map('delete');
    }

    /**
     * @param Persistence\Repositories\Categories $categoryRepository
     * @return void
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function onAfterMovingPlugin(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoryRepository,
    ): void
    {
        $pageId = $this->getPageId();

        // Update categories
        $result = $categoryRepository->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
            ],
        ]);

        foreach ($result as $category) {
            $category->setPageId($pageId);
            $category->save();
        }
    }

    /**
     *
     */
    public function getAdditives(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): \Frootbox\Db\Result
    {
        if (empty($this->getConfig('shareAdditives'))) {

            // Fetch additives
            $result = $additivesRepository->fetch([
                'where' => [ 'pluginId' => $this->getId() ],
                'order' => [ 'orderId ASC' ]
            ]);

            return $result;
        }
        else {

            // Fetch additives
            $result = $additivesRepository->fetch([
                'where' => [ ],
                'order' => [ 'orderId ASC' ]
            ]);

            return $result;
        }

    }

    /**
     * @param Persistence\Repositories\Categories $categoriesRepository
     * @return Persistence\Category|null
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
            ],
            'order' => [ 'lft ASC' ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function showCategoryAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoryRepository,

    ): Response
    {
        // Fetch category
        $category = $categoryRepository->fetchById($this->getAttribute('categoryId'));

        return new Response([
            'category' => $category,
        ]);
    }
}
