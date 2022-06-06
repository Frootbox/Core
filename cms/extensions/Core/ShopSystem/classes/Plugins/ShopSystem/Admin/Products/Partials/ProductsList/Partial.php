<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\ProductsList;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial {
    
    /**
     * 
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }


    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsReporitory
    )
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        if ($this->hasData('sort')) {
            $sort = $this->getData('sort');
            $order = [ $sort['column'] . ' ' . ($sort['direction'] == 'up' ? 'ASC' : 'DESC') ];
        }
        else {
            $order = [ 'title ASC' ];
        }

        // Fetch products
        $products = $productsReporitory->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => $order
        ]);

        $view->set('products', $products);
    }
}
