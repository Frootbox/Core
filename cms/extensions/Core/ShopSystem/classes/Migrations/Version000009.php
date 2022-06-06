<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000009 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die neuen Produkt-Optionen hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('CREATE TABLE `shop_products_options` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime DEFAULT NULL,
          `updated` datetime DEFAULT NULL,
          `productId` int(11) NOT NULL,
          `groupId` int(11) NOT NULL,
          `title` varchar(255) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->addSql('CREATE TABLE `shop_products_stocks` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `productId` int(11) NOT NULL,
          `config` text,
          `amount` int(11) DEFAULT NULL,
          `price` int(11) DEFAULT NULL,
          `groupKey` varchar(32) NOT NULL,
          `groupData` json DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }
}
