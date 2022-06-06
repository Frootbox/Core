<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt das Fax-Feld für Personen hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'ALTER TABLE `persons` ADD COLUMN `fax` VARCHAR(255) NULL DEFAULT NULL AFTER `phone2`;';

        try {
            $dbms->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
