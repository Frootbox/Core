<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Funktionalität der Datenfelder.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $this->addSql('ALTER TABLE `shop_products_data` ADD COLUMN `valueInt` INT NULL AFTER `valueText`;');
        $this->addSql('ALTER TABLE `shop_products_data` ADD COLUMN `type` VARCHAR(45) NULL DEFAULT \'Text\' AFTER `fieldId`;');
    }
}
