<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert Sprachunterstützung für Navigationen.';

    /**
     *
     */
    public function up(

    ): void
    {
        $this->addSql("ALTER TABLE `navigations_items` ADD COLUMN `language` VARCHAR(8) NOT NULL DEFAULT 'de-DE' AFTER `config`;");
    }
}
