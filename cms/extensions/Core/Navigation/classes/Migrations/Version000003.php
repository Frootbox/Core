<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit von Teaser-Aliassen.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasersRepository
    ): void
    {
        // Fetch teasers
        $result = $teasersRepository->fetch();

        foreach ($result as $teaser) {
            $teaser->save();
        }
    }
}
