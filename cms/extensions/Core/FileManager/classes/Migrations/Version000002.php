<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRepository
    ): void
    {
        // Fetch download widgets
        $result = $widgetsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\FileManager\Widgets\Downloads\Widget::class
            ]

        ]);

        foreach ($result as $widget) {

            if ($widget->getConfig('layoutId') == 'Index02') {

                $widget->addConfig([
                    'layoutId' => 'Index01'
                ]);

                $widget->save();
            }
        }
    }
}
