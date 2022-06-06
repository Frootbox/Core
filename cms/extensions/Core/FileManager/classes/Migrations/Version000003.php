<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Sprachunterstützung für Dateien hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `files` ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `config`;");
    }
}
