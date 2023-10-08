<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Blocks\ProductTeaser;

class Block extends \Frootbox\Persistence\Content\Blocks\Block
{
    /**
     *
     */
    public function getLimit(): int
    {
        return (!empty($this->getConfig('limit')) ? $this->getConfig('limit') : 4);
    }

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
    ): array
    {
        if (empty($this->getConfig('tags'))) {
            return [];
        }

        // Fetch products
        $products = $productRepository->fetchByTags($this->getConfig('tags'));

        return [
            'products' => $products,
        ];
    }
}
