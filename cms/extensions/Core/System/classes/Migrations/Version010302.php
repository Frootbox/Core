<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010302 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Sichtbarkeit von Admin-Apps ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `admin_apps` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `menuId`;");
        $this->addSql("ALTER TABLE `admin_apps` ADD COLUMN `access` VARCHAR(45) NULL DEFAULT 'Admin' AFTER `config`;");
    }
}
