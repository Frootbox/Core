<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt eine Kennzeichnung für mit KI bearbeitete Bilder hinzu.';

    public function getPreQueries(): array
    {
        return [
            'ALTER TABLE `files` ADD COLUMN `isAiEdited` TINYINT(1) NOT NULL DEFAULT 0 AFTER `isAiGenerated`;',
        ];
    }

    public function up(): void
    {

    }
}
