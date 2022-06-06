<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000011 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Mehrsprachigkeit für Personen hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `persons` ADD `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
    }
}
