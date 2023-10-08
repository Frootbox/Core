<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000016 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Sichtbarkeit von Schlagworten.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordRepository,
    ): void
    {
        // Fetch keywords
        $keywords = $keywordRepository->fetch();

        foreach ($keywords as $keyword) {

            if ($keyword->getVisibility() == 1) {

                $keyword->setVisibility(2);
                $keyword->save();
            }
        }
    }
}
