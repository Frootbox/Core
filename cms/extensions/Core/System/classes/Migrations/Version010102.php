<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010102 extends \Frootbox\AbstractMigration
{
    protected $description = 'Erweitert die Item-Connections.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            "ALTER TABLE `item_connections` ADD COLUMN `visibility` INT(11) NOT NULL DEFAULT '1' AFTER `uid`;",
            "ALTER TABLE `item_connections` ADD COLUMN `baseId` INT NOT NULL AFTER `itemClass`, ADD COLUMN `baseClass` VARCHAR(255) NOT NULL AFTER `baseId`;",
            "ALTER TABLE `item_connections` CHANGE COLUMN `uid` `uid` VARCHAR(255) NULL DEFAULT NULL ;",
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\ItemConnections $itemConnectionsRepository,
    ): void
    {
        // Fetch connections
        $result = $itemConnectionsRepository->fetch();

        foreach ($result as $itemConnection) {

            // Fix visibility
            $itemConnection->setVisibility(2);

            // Fix base entity
            if (empty($itemConnection->getBaseClass())) {
                $entity = $itemConnection->getEntityByUid();
                $itemConnection->setBaseId($entity->getId());
                $itemConnection->setBaseClass(get_class($entity));
            }

            $itemConnection->save();

        }
    }
}
