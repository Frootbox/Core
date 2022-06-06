<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Migrations;

class Version000008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Behebt ein Problem mit der Erstellung von Adressen.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): void
    {
        $this->addSql('ALTER TABLE `locations` CHANGE COLUMN `orderId` `orderId` INT(11) NOT NULL DEFAULT 0 ;');
    }
}
