<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010300 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Untertitel von generischen Assets ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `assets` ADD COLUMN `subtitle` VARCHAR(255) NULL DEFAULT NULL AFTER `title`;");
    }
}
