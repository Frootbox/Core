<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000201 extends \Frootbox\AbstractMigration
{
    protected $description = 'Ermöglicht mehrsprachige Werte in Produkt-Datenfeldern';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `shop_products_data` ADD COLUMN `valueTextI18n` JSON NULL AFTER `valueText`;");
    }
}
