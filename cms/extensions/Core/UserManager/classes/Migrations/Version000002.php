<?php
/**
 *
 */

namespace Frootbox\Ext\Core\UserManager\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $sql = 'ALTER TABLE `users` ADD COLUMN `type` VARCHAR(45) NOT NULL DEFAULT \'Admin\' AFTER `lastClick`;';

        try {
            $dbms->query($sql);
        }
        catch ( \Exception $e ) {
            // Ignore
        }
    }
}
