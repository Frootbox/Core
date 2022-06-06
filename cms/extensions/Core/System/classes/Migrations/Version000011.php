<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000011 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Sichtbarkeitsoptionen zu Aliasen hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `aliases` ADD COLUMN `visibility` INT NULL DEFAULT NULL AFTER `config`;';

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
