<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000008 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Personen-Datenbank.';

    /**
     *
     */
    public function up(
        \Frootbox\Db\Dbms\Interfaces\Dbms $dbms,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository,
        \Frootbox\Persistence\Content\Repositories\Texts $texts,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {

        $result = $files->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', 'Frootbox-Ext-Core-HelpAndSupport-Persistence-Repositories-Persons:%')
            ]
        ]);

        foreach ($result as $file) {

            $uid = $file->getUidRaw();

            $newUid = 'Frootbox-Ext-Core-HelpAndSupport-Persistence-Repositories-Contacts' . substr($uid, 65);

            $file->setUid($newUid);
            $file->save();
        }


        $result = $texts->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', 'Frootbox-Ext-Core-HelpAndSupport-Persistence-Repositories-Persons:%')
            ]
        ]);

        foreach ($result as $text) {

            $uid = $text->getUidRaw();

            $newUid = 'Frootbox-Ext-Core-HelpAndSupport-Persistence-Repositories-Contacts' . substr($uid, 65);

            $text->setUid($newUid);
            $text->save();
        }
    }
}
