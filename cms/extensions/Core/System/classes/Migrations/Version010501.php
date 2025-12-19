<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010501 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Logs.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `system_log` 
            ADD COLUMN `url` VARCHAR(255) NULL DEFAULT NULL AFTER `config`;");
    }
}
