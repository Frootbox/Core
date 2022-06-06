<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Instagram und Facebook hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `locations` ADD COLUMN `facebook` VARCHAR(255) NULL DEFAULT NULL AFTER `url`, ADD COLUMN `instagram` VARCHAR(255) NULL DEFAULT NULL AFTER `facebook`';

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
