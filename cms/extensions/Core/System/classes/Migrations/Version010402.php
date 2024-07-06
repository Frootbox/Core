<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010402 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sortierbarkeit bei generischen Kategorien.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `categories_2_items` 
            ADD COLUMN `orderId` INT NULL DEFAULT 0 AFTER `itemClass`;");
    }
}
