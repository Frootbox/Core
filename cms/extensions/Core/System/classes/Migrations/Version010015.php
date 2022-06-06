<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010015 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert Konfigurationen für Benutzer.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `users` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `type`;');
    }
}
