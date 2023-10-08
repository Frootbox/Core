<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert geklonte Preislisten.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoryRepository,
    ): void
    {
        // Fetch price lists
        $plugins = $contentElementRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Plugin::class,
            ],
        ]);

        foreach ($plugins as $plugin) {

            // Get plugins page
            $page = $plugin->getPage();

            // Fetch categories
            $categories = $categoryRepository->fetch([
                'where' => [
                    'pluginId' => $plugin->getId(),
                ],
            ]);

            foreach ($categories as $category) {

                // Update category
                $category->setPageId($page->getId());
                $category->setPluginId($plugin->getId());

                $category->save();

                // Fetch positions
                $positions = $category->getPositions([
                    'ignoreVisible' => true,
                    'ignoreDates' => true,
                ]);

                foreach ($positions as $position) {

                    // Update position
                    $position->setPageId($page->getId());
                    $position->setPluginId($plugin->getId());

                    $position->save();

                    foreach ($position->getPrices() as $price) {

                        // Update position
                        $price->setPageId($page->getId());
                        $price->setPluginId($plugin->getId());

                        $price->save();
                    }
                }
            }
        }
    }
}
