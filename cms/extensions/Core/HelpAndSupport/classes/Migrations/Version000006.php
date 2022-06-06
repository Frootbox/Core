<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die Alias-Funktion für Kontaktpersonen hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `alias` VARCHAR(255) NULL DEFAULT NULL AFTER `className`;';

        try {

            foreach ($queries as $sql) {
                $dbms->query($sql);
            }
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
