<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010401 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert das Konfigurationsfeld der Kategorien.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `categories` CHANGE COLUMN `config` `config` TEXT NULL DEFAULT NULL ;");
    }
}
