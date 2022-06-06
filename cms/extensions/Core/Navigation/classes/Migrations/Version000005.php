<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert verschachtelte Navigationspunkte.';

    /**
     *
     */
    public function up(

    ): void
    {
        $this->addSql("ALTER TABLE `navigations_items` ADD COLUMN `parentId` INT NOT NULL DEFAULT 0 AFTER `navId`;");
    }
}
