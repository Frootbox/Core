<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\VariantsList;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
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
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsReporitory
    ): void
    {
        // Obtain product
        $product = $this->getData('product');

        // Fetch variants
        $variants = $variantsReporitory->fetch([
            'where' => [ 'parentId' => $product->getId() ],
            'order' => [ 'title' ]
        ]);

        $view->set('variants', $variants);
    }
}
