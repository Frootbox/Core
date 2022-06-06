<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert den Admin Changelog.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `admin_changelog` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `date` datetime NOT NULL,
            `updated` datetime NOT NULL,
            `userId` int(11) NOT NULL,
            `pageId` int(11) DEFAULT NULL,
            `pluginId` int(11) DEFAULT NULL,
            `itemId` int(11) DEFAULT NULL,
            `itemClass` varchar(255) DEFAULT NULL,
            `action` varchar(255) DEFAULT NULL,
            `data` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $dbms->query($sql);
    }
}
