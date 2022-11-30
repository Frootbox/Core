<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000014 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Social-Media Verlinkungen zu Kontaktpersonen hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `persons` 
            ADD COLUMN `facebook` VARCHAR(255) NULL DEFAULT NULL AFTER `visibility`,
            ADD COLUMN `instagram` VARCHAR(255) NULL DEFAULT NULL AFTER `facebook`,
            ADD COLUMN `xing` VARCHAR(255) NULL DEFAULT NULL AFTER `instagram`,
            ADD COLUMN `linkedin` VARCHAR(255) NULL DEFAULT NULL AFTER `xing`,
            ADD COLUMN `twitter` VARCHAR(255) NULL DEFAULT NULL AFTER `linkedin`,
            CHANGE COLUMN `config` `config` TEXT NULL DEFAULT NULL AFTER `alias`;
        ');

        $this->addSql('ALTER TABLE `persons` 
            ADD COLUMN `youtube` VARCHAR(255) NULL DEFAULT NULL AFTER `twitter`,
            ADD COLUMN `twitch` VARCHAR(255) NULL DEFAULT NULL AFTER `youtube`;
        ');
    }
}
