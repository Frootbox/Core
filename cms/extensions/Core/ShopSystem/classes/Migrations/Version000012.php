<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000012 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Aufpreise für Optionen hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `shop_products_options` ADD COLUMN `surcharge` INT NULL DEFAULT NULL AFTER `title`;');
    }
}
