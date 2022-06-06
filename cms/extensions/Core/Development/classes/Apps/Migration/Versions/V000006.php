<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000006 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixAssetsVisibilities'
    ];

    /**
     *
     */
    public function fixAssetsVisibilities(
        \Frootbox\Db\Model $genericRepository
    ): void
    {
        // Configure general repository
        $genericRepository->setTable('assets');
        $result = $genericRepository->fetch([
            'where' => [
                'visibility' => 1
            ]
        ]);

        foreach ($result as $asset) {

            try {
                $asset->setVisibility(2);
                $asset->save();
            }
            catch ( \Exception $e ) {
                d($e);
                d($asset);
            }
        }
    }
}
