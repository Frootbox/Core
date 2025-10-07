<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Führt private Dateien ein.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            'ALTER TABLE `files` ADD COLUMN `isPrivate` TINYINT(1) NULL DEFAULT 0 AFTER `hash`;',
        ];
    }

    public function up(): void
    {

    }
}
