<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000001 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert die Block-Funktionalität.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'CREATE TABLE `blocks` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `date` datetime NOT NULL,
          `updated` datetime NOT NULL,
          `uid` varchar(255) CHARACTER SET utf8 NOT NULL,
          `blockId` varchar(45) CHARACTER SET utf8 NOT NULL,
          `orderId` int(11) DEFAULT NULL,
          `config` text CHARACTER SET utf8,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ';

        try {
            $dbms->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
