<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010009 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Admin-Apps Funktionalität.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $this->addSql('ALTER TABLE `admin_apps` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `menuId`;');
    }
}
