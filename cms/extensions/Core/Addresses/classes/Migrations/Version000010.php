<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000010 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Adressen um den Adresszusatz.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `locations` ADD COLUMN `addition` VARCHAR(255) NULL DEFAULT NULL AFTER `lng`;');
    }
}
