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
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository
     * @return array[]
     * @throws \Exception
     */
    public function onBeforeRendering(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
    ): array
    {
        $products = [];

        if (!empty($this->getConfig('tags'))) {

            // Fetch products
            $products = $productRepository->fetchByTags($this->getConfig('tags'));
        }
        elseif (!empty($this->getConfig('productId'))) {
            $products[] = $productRepository->fetchById($this->getConfig('productId'));
        }

        return [
            'products' => $products,
        ];
    }
}
