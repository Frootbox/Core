<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Initialisiert den Papierkorb für Dateien.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'CREATE TABLE `files_trash` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `date` DATETIME NOT NULL,
          `updated` DATETIME NOT NULL,
          `userId` INT NULL,
          `file` VARCHAR(255) NOT NULL,
          PRIMARY KEY (`id`));
        ';

        foreach ($queries as $sql) {

            try {
                $dbms->query($sql);
            }
            catch ( \Exception $e ) {
                // Ignore
            }
        }
    }
}
