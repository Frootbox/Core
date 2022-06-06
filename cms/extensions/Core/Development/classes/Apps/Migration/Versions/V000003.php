<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000003 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixBaseTextPluginClass'
    ];

    /**
     *
     */
    public function fixBaseTextPluginClass(
        \Frootbox\Persistence\Repositories\ContentElements $contentElements,
        \Frootbox\Persistence\Content\Repositories\Texts $textsRepository
    ): void
    {
        // Fetch old text plugins
        $result = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Persistence\Content\Elements\Text::class
            ]
        ]);

        foreach ($result as $element) {

            $oldUidBase = $element->getUidBase();

            // Update element
            $element->setType('Plugin');
            $element->setModel(\Frootbox\Persistence\Repositories\ContentElements::class);
            $element->setClassName(\Frootbox\Ext\Core\Editing\Plugins\Text\Plugin::class);
            $element->save();

            // Upadte text uids
            $texts = $textsRepository->fetchByUidBase($oldUidBase);

            foreach ($texts as $text) {

                $uidData = $text::extractUid($text->getDataRaw('uid'));
                $newUid = $element->getUid($uidData['segment']);

                $text->setUid($newUid);
                $text->save();
            }
        }
    }
}
