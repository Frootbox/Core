<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings;

class Version000200 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Nährstofftabellen ein';

    /**
     *
     */
    public function up(

    ): void
    {
        $this->addSql("CREATE TABLE `shop_products_nutrition` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `date` DATETIME NOT NULL,
            `updated` DATETIME NOT NULL,
            `productId` INT NOT NULL,
            `calorificValue` INT UNSIGNED NULL,
            `carbohydrates` INT UNSIGNED NULL,
            `carbohydratesOfWhichSugar` INT UNSIGNED NULL,
            `fat` INT UNSIGNED NULL,
            `fatUnsaturatedAcids` INT UNSIGNED NULL,
            `cholesterol` INT UNSIGNED NULL,
            `protein` INT UNSIGNED NULL,
            `sodium` INT UNSIGNED NULL,
            `potassium` INT UNSIGNED NULL,
            `dietaryFiber` INT UNSIGNED NULL,
            `salt` INT UNSIGNED NULL,
            PRIMARY KEY (`id`));");
    }
}
