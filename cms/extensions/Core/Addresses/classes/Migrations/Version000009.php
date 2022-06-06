<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000009 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Adressen-Tabelle um Unique-IDs.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): void
    {
        $this->addSql('ALTER TABLE `locations` ADD COLUMN `uid` VARCHAR(255) NULL DEFAULT NULL AFTER `className`;');
        $this->addSql('ALTER TABLE `locations` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
    }
}
