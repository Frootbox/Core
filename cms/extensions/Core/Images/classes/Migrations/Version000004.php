<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Migrations;

class Version000004 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Datumsanzeige von Referenzen.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,

    ): void
    {
        // Fetch references
        foreach ($referencesRepository->fetch() as $reference) {

            if (!empty($reference->getDateStart())) {
                continue;
            }

            $reference->setDateStart($reference->getDate());
            $reference->save();
        }
    }
}
