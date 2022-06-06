<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010022 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert auf die neuen Blöcke';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            "ALTER TABLE `blocks` 
                ADD COLUMN `vendorId` VARCHAR(255) NULL DEFAULT NULL AFTER `blockId`,
                ADD COLUMN `extensionId` VARCHAR(255) NULL DEFAULT NULL AFTER `vendorId`;",
            "ALTER TABLE `blocks` 
                ADD COLUMN `title` VARCHAR(255) NULL DEFAULT NULL AFTER `config`;",
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Blocks $blocksRepository,
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository,
    ): void
    {
        // Fetch extensions
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        // Fetch blocks
        $blocks = $blocksRepository->fetch();

        foreach ($blocks as $block) {

            foreach ($extensions as $extension) {

                $path = $extension->getExtensionController()->getPath();
                $blocksPath = $path . 'classes/Blocks/' . $block->getBlockId() . '/Block.html.twig';

                if (!file_exists($blocksPath)) {
                    continue;
                }

                $block->setVendorId($extension->getVendorId());
                $block->setExtensionId($extension->getExtensionId());

                $block->save();

                break;
            }
        }
    }
}
