<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Migrations;

class Version000007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert die Sichtbarkeits-Option für Navigationspunkte.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            "ALTER TABLE `navigations_items` ADD COLUMN `visibility` INT(11) NULL DEFAULT 1 AFTER `language`;",
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\NavigationsItems $navigationsItemsRepository,
    ): void
    {
        // Fetch navigation items
        $items = $navigationsItemsRepository->fetch();

        foreach ($items as $item) {

            $item->setVisibility(2);
            $item->save();
        }
    }
}
