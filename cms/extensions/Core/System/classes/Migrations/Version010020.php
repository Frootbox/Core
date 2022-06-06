<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010020 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Sortierbarkeit zu Kategorie-Verbindungen hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `newsletter_addresses` ADD COLUMN `fullname` VARCHAR(255) NULL DEFAULT NULL AFTER `lastname`;');
    }
}
