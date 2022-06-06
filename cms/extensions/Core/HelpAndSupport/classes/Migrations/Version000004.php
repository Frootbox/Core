<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Kategorie-Funktionalität.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];
        $queries[] = 'ALTER TABLE `categories_2_items` ADD COLUMN `orderId` INT NULL DEFAULT NULL AFTER `itemClass`;';
        $queries[] = 'ALTER TABLE `categories` ADD COLUMN `visibility` INT NULL DEFAULT 1 AFTER `className`;';

        try {

            foreach ($queries as $sql) {
                $dbms->query($sql);
            }
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
