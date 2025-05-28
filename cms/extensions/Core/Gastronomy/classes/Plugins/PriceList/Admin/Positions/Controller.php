<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Positions;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoriesRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        if (empty($post->get('title')) and empty($post->get('EntryId'))) {
            throw new \Frootbox\Exceptions\InputMissing('Bitte mindestens ein Feld ausfüllen.');
        }

        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        if (!empty($post->get('EntryId'))) {

            // Fetch list entry
            $listEntry = $listEntriesRepository->fetchById($post->get('EntryId'));
        }
        else {

            // Compose list entry
            $listEntry = new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\ListEntry([
                'title' => $post->get('title'),
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
                'config' => [
                    'price' => $post->get('price'),
                    'unit' => $post->get('unit')
                ]
            ]);

            // Insert new list entry
            $listEntry = $listEntriesRepository->insert($listEntry);
        }

        // Connect list entry to category
        $category->connectItem($listEntry);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#listEntriesCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Categories\Partials\ListEntries\Partial::class, [
                    'highlight' => $listEntry->getId(),
                    'plugin' => $this->plugin,
                    'category' => $category
                ])
            ]
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
     * @return Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): Response
    {
        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));

        // Update entry
        $listEntry->setTitle($post->get('title'));
        $listEntry->setNumber($post->get('number'));

        if (!empty($post->get('dateStart'))) {
            $listEntry->setDateStart($post->get('dateStart'));
        }

        if (!empty($post->get('dateEnd'))) {
            $listEntry->setDateEnd($post->get('dateEnd'));
        }

        // Set tags
        $listEntry->setTags($post->get('tags'));

        // Persist changes
        $listEntry->save();

        return self::getResponse('json', 200);
    }

    /**
     * @param \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxModalComposeAction(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
    ): Response
    {
        // Fetch available entries
        $entries = $listEntriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'title ASC' ],
        ]);

        return self::getResponse('plain', 200, [
            'Entries' => $entries,
        ]);
    }

    /**
     *
     */
    public function ajaxModalPriceEditAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Additives $additivesRepository
    ): Response
    {
        // Fetch price
        $price = $pricesRepository->fetchById($get->get('priceId'));
        $view->set('price', $price);

        // Fetch additives
        if ($this->plugin->getConfig('shareAdditives')) {

            $result = $additivesRepository->fetch([
                'order' => [ 'orderId ASC' ]
            ]);
        }
        else {
            $result = $additivesRepository->fetch([
                'where' => [ 'pluginId' => $this->plugin->getId() ],
                'order' => [ 'orderId ASC' ]
            ]);

            d($result);
        }

        $view->set('additives', $result);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxPanelPositionEditAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): Response
    {
        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));
        $view->set('listEntry', $listEntry);

        return self::getResponse();
    }

    /**
     *
     */
    public function ajaxPriceCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));

        // Add new price
        $price = $pricesRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Price([
            'pageId' => $listEntry->getPageId(),
            'pluginId' => $listEntry->getPluginId(),
            'parentId' => $listEntry->getId()
        ]));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#pricesReceiver',
                'html' =>  $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Positions\Partials\Prices::class, [
                    'highlight' => $price->getId(),
                    'listEntry' => $listEntry,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxPriceDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository
    ): Response
    {
        // Fetch price
        $price = $pricesRepository->fetchById($get->get('priceId'));

        // Delete price
        $price->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => 'tr[data-price="' . $price->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxPriceSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository
    ): Response
    {
        $orderId = count($get->get('row'));

        foreach ($get->get('row') as $priceId) {

            $price = $pricesRepository->fetchById($priceId);
            $price->setOrderId($orderId--);
            $price->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxPriceUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch price
        $price = $pricesRepository->fetchById($get->get('priceId'));

        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($price->getParentId());

        // Update price
        $price->setTitle($post->get('title'));
        $price->unsetConfig('additives');
        $price->addConfig([
            'price' => $post->get('price'),
            'unit' => $post->get('unit'),
            'addition' => $post->get('addition'),
            'priceFrom' => $post->get('priceFrom'),
            'additives' => $post->get('additives')
        ]);
        $price->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#pricesReceiver',
                'html' =>  $gp->injectPartial(\Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Positions\Partials\Prices::class, [
                    'highlight' => $price->getId(),
                    'listEntry' => $listEntry,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository,
    ): Response
    {
        $loopId = count($get->get('row')) + 1;

        // Loop entries
        foreach ($get->get('row') as $entryId) {

            // Fetch entry
            $entry = $listEntriesRepository->fetchById($entryId);
            $entry->setOrderId($loopId--);
            $entry->save();
        }

        return self::getResponse('json', 200, [ ]);
    }
}
