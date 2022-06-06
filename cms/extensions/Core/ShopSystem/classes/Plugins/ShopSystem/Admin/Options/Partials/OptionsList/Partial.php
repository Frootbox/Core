<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Options\Partials\OptionsList;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
    ): Response
    {
        // Obtain group
        $group = $this->getData('group');

        // Obtain product
        $product = $this->getData('product');

        // Fetch datasheet groups
        $result = $optionRepository->fetch([
            'where' => [
                'groupId' => $group->getId(),
                'productId' => $product->getId(),
            ],
        ]);

        return new Response(body: [
            'options' => $result,
        ]);
    }
}