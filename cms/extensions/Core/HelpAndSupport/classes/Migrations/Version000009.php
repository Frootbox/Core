<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000009 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert Konfigurationen für Personen.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `persons` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `visibility`;');
    }
}
