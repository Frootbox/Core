<?php
/**
 * @noinspection SqlNoDataSourceInspection
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010500 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Konfigurationen ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("CREATE TABLE `configuration_values` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `date` DATETIME NOT NULL,
            `updated` DATETIME NOT NULL,
            `configKey` VARCHAR(255) NOT NULL,
            `configValue` VARCHAR(255) NULL DEFAULT NULL,
            `type` VARCHAR(45) NULL DEFAULT 'String',
            `config` TEXT NULL DEFAULT NULL,
            PRIMARY KEY (`id`));");
    }
}
