<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000007 extends \Frootbox\AbstractMigration
{
    protected $description = 'Fügt die Sichtbarkeitseinstellungen für Kontaktpersonen hinzu.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository
    ): void
    {
        $queries = [];
        $queries[] = 'ALTER TABLE `persons` ADD COLUMN `visibility` INT NOT NULL DEFAULT 1 AFTER `zipcode`;';

        try {

            foreach ($queries as $sql) {
                $dbms->query($sql);
            }
        }
        catch ( \Exception $e ) {
            // Ignore
        }

        // Update contacts
        $result = $contactsRepository->fetch();

        foreach ($result as $contact) {
            $contact->setVisibility(2);
            $contact->save();
        }
    }
}
