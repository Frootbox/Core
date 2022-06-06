<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die Mobilfunknummer hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `locations` ADD COLUMN `mobile` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`;';

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
