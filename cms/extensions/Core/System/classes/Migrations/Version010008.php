<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Navigations-Funktionalität hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $this->addSql('CREATE TABLE `navigations` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `date` DATETIME NOT NULL,
            `updated` DATETIME NOT NULL,
            `navId` VARCHAR(255) NOT NULL,
            `title` VARCHAR(255) NULL DEFAULT NULL,
            PRIMARY KEY (`id`))');

        $this->addSql('CREATE TABLE `navigations_items` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `date` DATETIME NOT NULL,
            `updated` DATETIME NOT NULL,
            `navId` INT NOT NULL,
            `orderId` INT NULL,
            `title` VARCHAR(255) NULL DEFAULT NULL,
            `className` VARCHAR(255) NOT NULL,
            `config` TEXT NULL,
            PRIMARY KEY (`id`))');
    }
}
