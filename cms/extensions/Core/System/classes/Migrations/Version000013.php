<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000013 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert alte Text-Elemente.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): void
    {
        // Fetch old texts
        $result = $contentElementsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Persistence\Content\Elements\Text::class
            ],
        ]);

        foreach ($result as $plugin) {
            $plugin->setClassName(\Frootbox\Ext\Core\Editing\Plugins\Text\Plugin::class);
            $plugin->save();
        }
    }
}
