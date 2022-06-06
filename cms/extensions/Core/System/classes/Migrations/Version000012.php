<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000012 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit von Seiten-Aliassen.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = "ALTER TABLE `aliases` ADD COLUMN `section` VARCHAR(255) NOT NULL DEFAULT 'index' AFTER `visibility`;";
        $queries[] = "ALTER TABLE `aliases` ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `visibility`;";

        $queries[] = "ALTER TABLE `pages` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;";

        foreach ($queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }

        // Fetch pages
        $result = $pagesRepository->fetch();

        foreach ($result as $page) {
            $page->save();
        }
    }
}
