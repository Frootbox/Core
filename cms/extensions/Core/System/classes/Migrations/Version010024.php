<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010024 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Unterstützung für Mehrsprachigkeit hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `categories` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`; ');
    }
}
