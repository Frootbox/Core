<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt den Versandstatus und das Mindestalter hinzu.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `shop_products` ADD COLUMN `shippingState` INT(11) NULL DEFAULT 1 AFTER `shippingId`;');
        $this->addSql('ALTER TABLE `shop_products` ADD COLUMN `minimumAge` INT NULL DEFAULT NULL AFTER `packagingUnit`;');
    }
}
