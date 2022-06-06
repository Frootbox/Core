<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000005 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixSliderUids'
    ];

    /**
     *
     */
    public function fixSliderUids(
        \Frootbox\Persistence\Repositories\ContentElements $contentElements,
        \Frootbox\Persistence\Repositories\Files $filesRepository
    ): void
    {
        // Fetch old text plugins
        $plugins = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Images\Plugins\Slider\Plugin::class
            ]
        ]);

        foreach ($plugins as $plugin) {

            $children = $plugin->getPage()->getOffspring();

            foreach ($children as $page) {

                $uid = $page->getUid('files-' . $plugin->getId());

                // Check files
                $files = $filesRepository->fetch([
                    'where' => [
                        'uid' => $uid
                    ]
                ]);

                foreach ($files as $file) {

                    $newUid = $plugin->getUid('files-' . $page->getId());

                    $file->setUid($newUid);
                    $file->save();
                }
            }
        }
    }
}
