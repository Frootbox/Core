<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit von Preislisten-Einträgen.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): void
    {
        // Fetch entries
        $entries = $listEntriesRepository->fetch();

        foreach ($entries as $entry) {

            if ($entry->getVisibility() != 1) {
                continue;
            }

            $entry->setVisibility(2);
            $entry->save();
        }
    }
}
