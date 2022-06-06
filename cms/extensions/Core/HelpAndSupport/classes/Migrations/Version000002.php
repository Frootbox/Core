<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt eine weiteres Telefonfeld für Personen hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'ALTER TABLE `persons` ADD COLUMN `phone2` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`;';

        try {
            $dbms->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
