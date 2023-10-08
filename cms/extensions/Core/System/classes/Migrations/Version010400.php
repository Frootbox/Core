<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010400 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die UIDs für URL-Aliase ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `aliases` ADD COLUMN `uid` VARCHAR(255) NULL DEFAULT NULL AFTER `itemModel`;");
    }
}
