<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010025 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Blockfunktionalität';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `blocks` ADD COLUMN `pageId` INT NULL DEFAULT NULL AFTER `updated`;');
    }
}
