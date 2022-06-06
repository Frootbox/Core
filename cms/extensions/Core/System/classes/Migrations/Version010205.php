<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010205 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit der Seiten-Aliasse.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
    ): void
    {
        // Fetch pages
        $result = $pagesRepository->fetch();

        foreach ($result as $page) {
            $page->save();
        }
    }
}
