<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Configuration;

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

    public function ajaxAdditiveDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository,
    ): Response
    {
        // Fetch additives
        $additive = $additivesRepository->fetchById($get->get('additiveId'));

        // Delete additive
        $additive->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => '[data-additive="' . $additive->getId() . '"]',
        ]);
    }

    public function ajaxCategoriesTriggerSaveAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        $categories = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'lft ASC' ],
        ]);

        foreach ($categories as $category) {
            $category->save();
        }

        d("OK");
    }

    public function ajaxModalAdditivesDuplicatesAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntryRepository,
    ): Response
    {
        // Build query
        $sql = 'SELECT 
            COUNT(id) as count,
            GROUP_CONCAT(id) as ids,
            title
        FROM
            assets
        WHERE
            className = :className
        GROUP BY
            title
        HAVING
            count > 1';

        // Fetch duplicates
        $additives = $additivesRepository->fetchByQuery($sql, [
            'className' => \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Additive::class,
        ]);

        $listEntries = $listEntryRepository->fetch();

        foreach ($additives as $additive) {

            $ids = explode(',', $additive->getIds());
            $id = array_shift($ids);

            foreach ($listEntries as $listEntry) {

                foreach ($listEntry->getPrices() as $price) {

                    $additivesConfig = $price->getConfig('additives');

                    if (empty($additivesConfig)) {
                        continue;
                    }

                    foreach ($ids as $oldId) {

                        $oldId = trim($oldId);

                        if (in_array($oldId, $additivesConfig)) {

                            if (($key = array_search($oldId, $additivesConfig)) !== false) {
                                unset($additivesConfig[$key]);
                            }

                            $additivesConfig[] = trim($id);


                            $price->unsetConfig('additives');
                            $price->addConfig([
                                'additives' => $additivesConfig,
                            ]);
                            $price->save();
                            break;
                        }
                    }
                }
            }
        }

        return self::getResponse('json', 200, [
            'success' => 'Fertig.',
        ]);
    }

    /**
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntryRepository
     * @return Response
     */
    public function ajaxModalAdditivesUnusedAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntryRepository,
    ): Response
    {
        $additives = [];
        $unused = [];

        foreach ($listEntryRepository->fetch() as $entry) {
            foreach ($entry->getPrices() as $price) {

                if (empty($price->getConfig('additives'))) {
                    continue;
                }

                foreach ($price->getConfig('additives') as $id) {
                    $additives[$id] = $id;
                }

            }
        }

        // Fetch additives
        $result = $additivesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'title ASC' ],
        ]);

        foreach ($result as $additive) {

            if (empty($additives[$additive->getId()])) {
                $unused[] = $additive;
            }
        }

        return self::getResponse('plain', 200, [
            'additives' => $unused,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->plugin->unsetConfig('shareAdditives');

        $this->plugin->addConfig([
            'shareAdditives' => $post->get('ShareAdditives'),
        ]);

        $this->plugin->save();

        return self::getResponse('json', 200);
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