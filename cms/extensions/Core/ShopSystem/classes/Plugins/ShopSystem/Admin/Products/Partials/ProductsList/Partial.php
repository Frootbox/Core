<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\ProductsList;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsReporitory
    ): \Frootbox\Admin\Controller\Response
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        if ($this->hasData('sort')) {
            $sort = $this->getData('sort');
            $order = [ $sort['column'] . ' ' . ($sort['direction'] == 'up' ? 'ASC' : 'DESC') ];
        }
        elseif (!empty($plugin->getConfig('ui.productListSorting'))) {

            if ($plugin->getConfig('ui.productListSorting') == 'DateDesc') {
                $order = [ 'date DESC' ];
            }
            else {
                $order = [ 'title ASC' ];
            }
        }
        else {
            $order = [ 'title ASC' ];
        }

        // Fetch products
        $products = $productsReporitory->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => $order
        ]);

        return new Response(body: [
            'products' => $products,
        ]);
    }
}
