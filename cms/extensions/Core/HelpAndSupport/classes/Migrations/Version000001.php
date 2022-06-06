<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000001 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die Personen Funktionalität hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {

        $sql = 'CREATE TABLE `persons` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `pluginId` int(11) DEFAULT NULL,
          `pageId` int(11) DEFAULT NULL,
          `orderId` int(11) DEFAULT NULL,
          `className` varchar(255) CHARACTER SET utf8 NOT NULL,
          `gender` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
          `title` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `firstName` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `lastName` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `phone` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `email` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
          `position` varchar(255) DEFAULT NULL,
          `company` varchar(255) DEFAULT NULL,
          `street` varchar(255) DEFAULT NULL,
          `streetNumber` varchar(45) DEFAULT NULL,
          `city` varchar(255) DEFAULT NULL,
          `zipcode` varchar(45) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

        try {
            $dbms->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
