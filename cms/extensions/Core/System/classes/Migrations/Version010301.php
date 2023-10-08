<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010301 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Sticky-Funktion für generische Assets ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `assets` ADD COLUMN `isSticky` TINYINT(1) NULL DEFAULT 0 AFTER `visibility`;");
    }
}
