<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000013 extends \Frootbox\AbstractMigration
{
    protected $description = 'Textlänge unbegrenzt in Produkt-Datenfeldern';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `shop_products_data` CHANGE COLUMN `valueText` `valueText` TEXT CHARACTER SET 'utf8' NULL DEFAULT NULL ;");
    }
}
