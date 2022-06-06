<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010012 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Funktionalität Standorten.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `assets` ADD COLUMN `locationId` INT NULL DEFAULT NULL AFTER `alias`;');
    }
}
