<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Initialisiert die Sichtbarkeit von Teasern.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasersRepository
    ): void
    {
        // Fetch teasers
        $result = $teasersRepository->fetch([
            'where' => [
                'visibility' => 1,
            ],
        ]);

        foreach ($result as $teaser) {
            $teaser->setVisibility(2);
            $teaser->save();
        }
    }
}
