<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Unterstützung für Artikelnummern hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [ ];

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `itemNumber` VARCHAR(255) NULL DEFAULT NULL AFTER `manufacturerId`;
        EOT;

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `shippingId` INT NULL DEFAULT NULL AFTER `itemNumber`;
        EOT;

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `packagingSize` INT(11) NULL DEFAULT '1' AFTER `shippingId`;
        EOT;

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `packagingUnit` VARCHAR(45) NULL DEFAULT 'Each' AFTER `packagingSize`;
        EOT;

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `packagingUnit`;
        EOT;

        foreach ($queries as $query) {

            try {
                $dbms->query($query);
            }
            catch ( \PDOException $e ) {
                continue;
            }
        }
    }
}
