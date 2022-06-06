<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000010 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Aliasse für Personen.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepositories
    ): void
    {
        $result = $aliasesRepositories->fetch([
            'where' => [
                'itemModel' => 'Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Persons',
            ],
        ]);

        foreach ($result as $alias) {
            $alias->setItemModel('Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts');
            $alias->save();
        }
    }
}
