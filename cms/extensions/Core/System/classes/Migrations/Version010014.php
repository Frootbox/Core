<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010014 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert Mehrsprachigkeit für Seiten.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `pages` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
        $this->addSql("ALTER TABLE `aliases` ADD COLUMN `section` VARCHAR(255) NOT NULL DEFAULT 'index' AFTER `visibility`;");
        $this->addSql("ALTER TABLE `aliases` ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `visibility`;");
        $this->addSql("ALTER TABLE `content_texts` ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `config`;");
        $this->addSql("ALTER TABLE `assets` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;");
    }
}
