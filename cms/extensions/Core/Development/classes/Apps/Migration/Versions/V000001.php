<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000001 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixGrids'
    ];

    /**
     * Fix erroneous grid sockets
     */
    public function fixGrids(
        \Frootbox\Persistence\Repositories\ContentElements $contentElements
    ): void
    {
        // Fetch grids
        $result = $contentElements->fetch([
            'where' => [ 'type' => 'Grid' ]
        ]);

        foreach ($result as $grid) {

            foreach ($grid->getColumns() as $column) {

                $oldSocket = preg_replace('#_(\d{1,})_#', '_', $column['socket']);

                $elements = $contentElements->fetch([
                    'where' => [
                        'pageId' => $column['pageId'],
                        'socket' => $oldSocket
                    ]
                ]);

                foreach ($elements as $element) {
                    $element->setSocket($column['socket']);
                    $element->save();
                }
            }
        }
    }
}
