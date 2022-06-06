<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Unterstützung für Geo-Coding hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `locations` ADD COLUMN `lat` FLOAT NULL DEFAULT NULL AFTER `visibility`, ADD COLUMN `lng` FLOAT NULL DEFAULT NULL AFTER `lat`;';

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
