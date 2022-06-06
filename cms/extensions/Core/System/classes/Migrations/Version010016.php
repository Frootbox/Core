<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010016 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Log-Funktionalität.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `logs` ADD COLUMN `parentId` INT NULL DEFAULT NULL AFTER `pluginId`;');
    }
}
