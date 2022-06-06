<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010203 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert den Suchindex.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `aliases` ADD COLUMN `lastIndex` DATETIME NULL DEFAULT NULL AFTER `section`;');
    }
}
