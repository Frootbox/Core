<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;

class Version000006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Verpackungsgrößen auf Tausendstel Präzision.';

    /**
     *
     */
    public function up(
        Products $productsRepository
    ): void
    {
        // Fetch all products
        $result = $productsRepository->fetch();

        foreach ($result as $product) {
            $product->setPackagingSize($product->getpackagingSize() * 10);
            $product->save();
        }
    }
}
