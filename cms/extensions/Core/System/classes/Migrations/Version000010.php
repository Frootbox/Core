<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000010 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Ausrichtung der Widgets.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRepository
    ): void
    {
        // Fetch widgets
        $result = $widgetsRepository->fetch();

        foreach ($result as $widget) {

            if ($widget->getConfig('width') == 12) {
                $widget->addconfig([
                    'align' => 'justify',
                ]);
                $widget->save();
            }
        }
    }
}
