<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010101 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Benutzerprofile.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `users` 
            ADD COLUMN `firstName` VARCHAR(255) NULL DEFAULT NULL AFTER `config`,
            ADD COLUMN `lastName` VARCHAR(255) NULL DEFAULT NULL AFTER `firstName`;");
    }
}
