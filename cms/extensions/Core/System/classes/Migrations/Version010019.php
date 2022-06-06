<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010019 extends \Frootbox\AbstractMigration
{
    protected $description = 'Datenbankanpassung des Seiten-Typs.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            "ALTER TABLE `pages` ADD COLUMN `type` VARCHAR(45) NOT NULL DEFAULT 'Default' AFTER `visibility`;",
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
    ): void
    {
        // Fetch pages
        $pages = $pagesRepository->fetch();

        foreach ($pages as $page) {

            if (empty($page->getConfig('pageType'))) {
                continue;
            }

            $page->setType($page->getConfig('pageType'));
            $page->save();
        }
    }
}
