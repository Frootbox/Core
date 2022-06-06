<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Verbessert das Handling von Adressen.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql('ALTER TABLE `locations` ADD COLUMN `visibility` INT(11) NOT NULL DEFAULT \'1\' AFTER `url`;');
    }
}
