<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt Checksummen-Hashes zu Dateien hinzu.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            'ALTER TABLE `files` ADD COLUMN `hash` VARCHAR(32) NULL DEFAULT NULL AFTER `language`;',
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Files $filesRepository,
    ): void
    {
        // Add hashes
        $result = $filesRepository->fetch();

        foreach($result as $file) {

            $path = FILES_DIR . $file->getPath();

            if (!file_exists($path)) {
                continue;
            }

            $hash = md5_file($path);

            $file->setHash($hash);
            $file->save();
        }
    }
}
