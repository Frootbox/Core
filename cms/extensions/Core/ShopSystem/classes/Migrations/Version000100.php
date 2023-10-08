<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings;

class Version000100 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die externe Artikelnummer hinzu.';

    /**
     *
     */
    public function up(

    ): void
    {
        $this->addSql("ALTER TABLE `shop_products` ADD COLUMN `itemNumberExternal` VARCHAR(255) NULL DEFAULT NULL AFTER `itemNumber`;");
    }
}
