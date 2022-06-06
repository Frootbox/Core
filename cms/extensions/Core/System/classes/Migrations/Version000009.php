<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000009 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert die Personen-Tabelle.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `phone2` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`;';
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `fax` VARCHAR(255) NULL DEFAULT NULL AFTER `phone2`;';
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `street` VARCHAR(255) NULL DEFAULT NULL AFTER `company`;';
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `streetNumber` VARCHAR(255) NULL DEFAULT NULL AFTER `street`;';
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `zipcode` VARCHAR(255) NULL DEFAULT NULL AFTER `streetNumber`;';
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `city` VARCHAR(255) NULL DEFAULT NULL AFTER `zipcode`;';

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
