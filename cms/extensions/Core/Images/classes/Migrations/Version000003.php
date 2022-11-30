<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Migrations;

class Version000003 extends \Frootbox\AbstractMigration
{
    protected $description = 'Räumt die Datenbank auf.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): void
    {
        // Fetch references
        foreach ($referencesRepository->fetch() as $reference) {

            try {
                $reference->getPlugin();
            }
            catch ( \Exception $e) {
                $reference->delete();
            }
        }
    }
}
