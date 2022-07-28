<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ContactForms\Migrations;

class Version000002 extends \Frootbox\AbstractMigration
{
    protected $description = 'Reset der Formular-Gruppen Titel.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
    ): void
    {

        // Fetch download widgets
        $result = $groupsRepository->fetch([
            'where' => [

            ],
        ]);

        foreach ($result as $group) {

            if (empty($group->getTitle())) {
                continue;
            }

            $group->setTitle(null);
            $group->save();
        }
    }
}
