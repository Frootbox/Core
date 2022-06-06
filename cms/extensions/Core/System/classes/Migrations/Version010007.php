<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Initialisiert globale Tags';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `itemId` int(11) NOT NULL,
  `itemClass` varchar(255) CHARACTER SET utf8 NOT NULL,
  `config` text CHARACTER SET utf8,
  `pageId` int(11) DEFAULT NULL,
  `pluginId` int(11) DEFAULT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

        foreach ($queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }
    }
}
