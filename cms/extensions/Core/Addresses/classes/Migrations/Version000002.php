<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Verbessert die Sortierbarkeit von Adressen.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'ALTER TABLE `locations` CHANGE COLUMN `orderId` `orderId` INT(11) NULL DEFAULT \'0\' ;';
        $dbms->query($sql);
    }
}
