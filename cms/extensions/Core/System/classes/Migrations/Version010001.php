<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010001 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Sichtbarkeit für Kategorien hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = "ALTER TABLE `categories` ADD COLUMN `visibility` INT(11) NOT NULL DEFAULT '1' AFTER `alias`;";

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
