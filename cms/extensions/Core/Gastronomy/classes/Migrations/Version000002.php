<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Initialisiert die Preislisten.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\Prices $pricesRepository,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): void
    {
        // Fetch entries
        $entries = $listEntriesRepository->fetch();

        foreach ($entries as $entry) {

            $data = $entry->getData();

            $price = $pricesRepository->insert(new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Price([
                'pageId' => $entry->getPageId(),
                'pluginId' => $entry->getPluginId(),
                'parentId' => $entry->getId(),
                'config' => $data['config']
            ]));
        }
    }
}
