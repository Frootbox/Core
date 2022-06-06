<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000001 extends \Frootbox\AbstractMigration
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
        CREATE TABLE `shop_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `pageId` int(11) NOT NULL,
          `pluginId` int(11) NOT NULL,
          `datasheetId` int(11) NOT NULL,
          `title` varchar(255) CHARACTER SET utf8 NOT NULL,
          `alias` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `price` float DEFAULT NULL,
          `taxrate` float DEFAULT NULL,
          `isFree` int(11) DEFAULT NULL,
          `itemNumber` varchar(255) DEFAULT NULL,
          `config` text,
          `shippingId` int(11) DEFAULT NULL,
          `packagingSize` int(11) DEFAULT '1',
          `packagingUnit` varchar(45) DEFAULT 'Each',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        EOT;

        $queries[] = <<<EOT
        CREATE TABLE `shop_products_data` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `productId` int(11) NOT NULL,
          `fieldId` int(11) NOT NULL,
          `valueText` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
