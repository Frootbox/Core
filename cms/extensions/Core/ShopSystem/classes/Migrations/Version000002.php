<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [ ];

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `visibility` INT(1) NULL DEFAULT 0 AFTER `isFree`;

        EOT;

        $queries[] = <<<EOT
        ALTER TABLE `shop_products` ADD COLUMN `manufacturerId` INT NULL DEFAULT NULL AFTER `visibility`;
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
