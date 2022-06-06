<?php
/**
 *
 */

namespace Frootbox\Ext\Core\FileManager\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Ändert die Konfiguration der Überschriften in Download-Widgets.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository,
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRepository
    ): void
    {
        // Fetch download widgets
        $widgets = $widgetsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\FileManager\Widgets\Downloads\Widget::class,
            ],
        ]);

        foreach ($widgets as $widget) {

            $title = $widget->getConfig('title');

            if (empty($title)) {
                continue;
            }

            $widget->unsetConfig('title');
            $widget->addConfig([
                'withHeadline' => true,
            ]);

            $widget->save();

            $textsRepository->insert(new \Frootbox\Persistence\Content\Text([
                'userId' => '{userId}',
                'uid' => $widget->getUid('title'),
                'text' => $title,
                'config' => [
                    'headline' => $title,
                ],
            ]));
        }
    }
}
