<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert die Assets-Tabelle.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `assets` ADD COLUMN `customClass` VARCHAR(255) NULL DEFAULT NULL AFTER `className`;';
        $queries[] = 'ALTER TABLE `assets` ADD COLUMN `uid` VARCHAR(255) NULL DEFAULT NULL AFTER `customClass`;';

        foreach ($queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }
    }
}
