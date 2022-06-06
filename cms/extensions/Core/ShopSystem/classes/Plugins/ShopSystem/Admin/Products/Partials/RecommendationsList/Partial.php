<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\RecommendationsList;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): void
    {
        // Obtain product
        $product = $this->getData('product');

        $list = $product->getConfig('recommendations') ?? [];

        foreach ($list as $index => $recommendation) {

            // Fetch recommendation
            $product = $productsRepository->fetchById($recommendation['productId']);

            $list[$index]['product'] = $product;
        }

        $view->set('recommendations', $list);
    }
}
