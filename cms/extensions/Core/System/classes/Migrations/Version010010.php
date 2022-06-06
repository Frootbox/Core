<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010010 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Personen-Tabelle.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $this->addSql('ALTER TABLE `persons` ADD COLUMN `config` TEXT NULL DEFAULT NULL AFTER `alias`;');
    }
}
