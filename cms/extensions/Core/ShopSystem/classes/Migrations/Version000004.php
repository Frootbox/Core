<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Unterstützung für Sortierung von Produkten hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [ ];

        $queries[] = <<<EOT
        ALTER TABLE `categories_2_items` ADD COLUMN `orderId` INT NULL AFTER `pageId`;

        EOT;


        foreach ($queries as $query) {

            try {
                $dbms->query($query);
            }
            catch ( \PDOException $e ) {
                continue;
            }
        }
    }
}
