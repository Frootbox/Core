<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt eine Kennzeichnung für KI-generierte Bilder hinzu.';

    public function getPreQueries(): array
    {
        return [
            'ALTER TABLE `files` ADD COLUMN `isAiGenerated` TINYINT(1) NOT NULL DEFAULT 0 AFTER `isPrivate`;',
        ];
    }

    public function up(): void
    {

    }
}
