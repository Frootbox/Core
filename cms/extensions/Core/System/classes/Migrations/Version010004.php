<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version010004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktiviert die SEO-Optionen für die Startseite.';

    /**
     *
     */
    public function getPreQueries(): array
    {
        return [
            "ALTER TABLE `aliases` 
                ADD COLUMN `language` VARCHAR(8) CHARACTER SET 'utf8' NOT NULL DEFAULT 'de-DE' AFTER `visibility`,
                ADD COLUMN `section` VARCHAR(255) NOT NULL DEFAULT 'index' AFTER `language`;",
        ];
    }

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): void
    {


        // Fetch root page
        $page = $pagesRepository->fetchOne([
            'where' => [
                'parentId' => 0,
            ],
        ]);

        if (empty($page)) {

            // Insert root page
            $page = $pagesRepository->insertRoot(new \Frootbox\Persistence\Page([
                'title' => 'Startseite',
                'language' => DEFAULT_LANGUAGE,
            ]));
        }

        $page->save();
    }
}
