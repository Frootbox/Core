<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Behebt ein Problem mit der Anzeige von Texten in Blöcken.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Texts $texts
    ): void
    {
        $result = $texts->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', 'Frootbox-Persistence-Content-Repositories-Blocks:%')
            ]
        ]);

        foreach ($result as $text) {

            $uid = $text->getUidRaw();

            $newUid = 'Frootbox-Persistence-Content-Blocks-Repositories-Blocks' . substr($uid, 48);

            $text->setUid($newUid);
            $text->save();
        }
    }
}
