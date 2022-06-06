<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010202 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt den System-Log ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('CREATE TABLE `system_log` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `date` DATETIME NOT NULL,
          `updated` DATETIME NOT NULL,
          `userId` INT NOT NULL,
          `log_code` VARCHAR(255) NOT NULL,
          `config` TEXT NULL DEFAULT NULL,
          PRIMARY KEY (`id`));');
    }
}
