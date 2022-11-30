<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000011 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Datenbankfelder für Längen- und Breitengerade.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `locations` CHANGE COLUMN `lat` `lat` DECIMAL(8,6) NULL DEFAULT NULL , CHANGE COLUMN `lng` `lng` DECIMAL(9,6) NULL DEFAULT NULL ;');
    }
}
