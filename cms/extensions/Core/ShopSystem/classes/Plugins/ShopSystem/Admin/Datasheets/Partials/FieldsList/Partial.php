<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\FieldsList;

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
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetFields
    )
    {
        // Obtain datasheet
        $datasheet = $this->getData('datasheet');

        // Fetch datasheet fields
        $result = $datasheetFields->fetch([
            'where' => [ 'parentId' => $datasheet->getId() ]
        ]);

        $list = [];

        foreach ($result as $field) {
            $list[$field->getSection()][] = $field;
        }

        $view->set('datasheetFields', $list);
    }
}