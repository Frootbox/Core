<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010100 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Item-Connections ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("CREATE TABLE `item_connections` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `itemId` int(11) NOT NULL,
          `itemClass` varchar(255) NOT NULL,
          `orderId` int(11) NOT NULL DEFAULT '0',
          `uid` varchar(255) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }
}
