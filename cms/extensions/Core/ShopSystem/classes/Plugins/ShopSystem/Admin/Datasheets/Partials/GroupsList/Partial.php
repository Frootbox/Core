<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\GroupsList;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {
    
    /**
     * 
     */
    public function getPath ( ): string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    )
    {
        // Obtain datasheet
        $datasheet = $this->getData('datasheet');

        // Fetch datasheet groups
        $result = $datasheetOptionGroupRepository->fetch([
            'where' => [
                'parentId' => $datasheet->getId(),
            ],
        ]);

        return new Response(body: [
            'groups' => $result,
        ]);
    }
}