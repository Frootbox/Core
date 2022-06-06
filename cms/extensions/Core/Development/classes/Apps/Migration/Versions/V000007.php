<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

use Frootbox\Persistence\Content\Repositories\Widgets;

class V000007 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixThumbnailsCaptionsAndCopyrights'
    ];

    /**
     *
     */
    public function fixThumbnailsCaptionsAndCopyrights(
        \Frootbox\Persistence\Content\Repositories\Widgets $widgetsRepository,
        \Frootbox\Persistence\Repositories\Files $files
    ): void
    {
        // Fetch widgets
        $result = $widgetsRepository->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Images\Widgets\Thumbnail\Widget::class
            ]
        ]);

        foreach ($result as $widget) {

            if (empty($widget->getConfig('caption')) and empty($widget->getConfig('copyright'))) {
                continue;
            }

            // Fetch file
            if (!$file = $files->fetchByUid($widget->getUid('image'))) {
                continue;
            }

            // Update file
            $file->setCopyright($widget->getConfig('copyright'));
            $file->addConfig([
                'caption' => $widget->getConfig('caption')
            ]);
            $file->save();

            // Update widget
            $widget->unsetConfig('caption');
            $widget->unsetConfig('copyright');

            $widget->save();
        }
    }
}
