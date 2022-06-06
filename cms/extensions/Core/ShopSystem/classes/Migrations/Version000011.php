<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000011 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Mehrsprachigkeit für Shop-Artikel hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `shop_products` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
        $this->addSql('ALTER TABLE `categories_2_items` ADD COLUMN `aliases` TEXT NULL DEFAULT NULL AFTER `alias`;');
    }
}
