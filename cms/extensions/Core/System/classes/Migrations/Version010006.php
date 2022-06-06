<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Initialisiert das "Last-Click"-Tracking für Benutzer.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `users` ADD COLUMN `lastClick` DATETIME NULL AFTER `password`;';

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
