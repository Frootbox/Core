<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Ermöglicht Emojis in Formular-Einsendungen';

    /**
     *
     */
    public function up(): void
    {
        $this->addSql("ALTER TABLE `logs` CHANGE `logdata` `logdata` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;");
    }
}
