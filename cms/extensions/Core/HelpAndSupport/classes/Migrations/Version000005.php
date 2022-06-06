<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000005 extends \Frootbox\AbstractMigration
{
    protected $description = 'Korrigiert die Repositories der Jobs.';

    /**
     *
     */
    public function up(
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): void
    {
        $texts = $textsRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('uid', 'Frootbox-Ext-Core-HelpAndSupport-Plugins-Jobs-Persistence-Repositories-Keywords:%')
            ]
        ]);

        foreach ($texts as $text) {

            $uidSegments = $text::extractUid($text->getUidRaw());

            $uid = substr($text->getUidRaw(), 0, 71) . 'Jobs:' . $uidSegments['id'] . ':' . $uidSegments['segment'];

            $text->setUid($uid);
            $text->save();
        }
    }
}
