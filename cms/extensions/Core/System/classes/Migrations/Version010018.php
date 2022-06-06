<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010018 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Assets um Benutzerzuordnung.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `assets` ADD COLUMN `userId` INT NULL DEFAULT NULL AFTER `uid`;');
    }
}
