<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000013 extends \Frootbox\AbstractMigration
{
    protected $description = 'Räumt alte Personen-Aliasse auf.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Aliases $aliasRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository,
    ): void
    {
        // Fetch all contact aliases
        $result = $aliasRepository->fetch([
            'where' => [
                'itemModel' => \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts::class,
            ],
        ]);

        foreach ($result as $alias) {

            try {
                $contact = $contactsRepository->fetchById($alias->getItemId());
            }
            catch ( \Exception $e ) {
                $alias->delete();
            }
        }
    }
}
