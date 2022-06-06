<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010017 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert die Mehrsprachigkeit für Personen.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `persons` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
    }
}
