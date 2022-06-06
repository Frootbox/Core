<?php
/**
 *
 */

namespace Frootbox\Ext\Core\System\Migrations;

class Version000006 extends \Frootbox\AbstractMigration
{
    protected $description = 'Behebt ein Problem mit der Anzeige von Bilder in Blöcken.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): void
    {
        $result = $filesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', 'Frootbox-Persistence-Content-Repositories-Blocks:%')
            ]
        ]);

        foreach ($result as $file) {

            $uid = $file->getUidRaw();

            $newUid = 'Frootbox-Persistence-Content-Blocks-Repositories-Blocks' . substr($uid, 48);

            $file->setUid($newUid);
            $file->save();
        }
    }
}
