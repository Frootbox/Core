<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Products\Partials\StocksList;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
    ): Response
    {
        // Obtain product
        $product = $this->getData('product');

        // Fetch stocks
        $stocks = $stockRepository->fetch([
            'where' => [
                'productId' => $product->getId(),
            ],
        ]);

        return new Response(body: [
            'stocks' => $stocks,
        ]);
    }
}
