<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010206 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt die Sichtbarkeit der Blöcke ein.';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `blocks` ADD COLUMN `visibility` INT(11) NOT NULL DEFAULT '2' AFTER `title`;");
    }
}
