<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert die Sichbarkeitseinstellungen der Content-Elemente (2/2).';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms
    ): void
    {
        $queries = [];

        $queries[] = 'ALTER TABLE `content_elements` CHANGE COLUMN `visibility` `visibility` INT NOT NULL DEFAULT 1 ;';

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
