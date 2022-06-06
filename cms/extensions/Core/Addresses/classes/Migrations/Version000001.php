<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000001 extends \Frootbox\AbstractMigration
{
    protected $description = 'Installiert die Adress-Datenbank.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $this->addSql('CREATE TABLE `locations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `pageId` int(11) DEFAULT NULL,
          `pluginId` int(11) DEFAULT NULL,
          `orderId` int(11) NOT NULL,
          `className` varchar(255) CHARACTER SET utf8 NOT NULL,
          `title` varchar(255) CHARACTER SET utf8 NOT NULL,
          `config` text CHARACTER SET utf8,
          `street` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `streetNumber` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `zipcode` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `city` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `country` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `alias` varchar(255) DEFAULT NULL,
          `phone` varchar(255) DEFAULT NULL,
          `email` varchar(255) DEFAULT NULL,
          `fax` varchar(255) DEFAULT NULL,
          `url` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    }
}
