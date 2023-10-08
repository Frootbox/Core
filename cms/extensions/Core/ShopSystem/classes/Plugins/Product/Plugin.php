<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Product;

use Frootbox\Config\Config;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     * @param Config $configuration
     * @return array
     */
    public function getCountries(
        \Frootbox\Config\Config $configuration,
    ): array
    {
        if (!empty($configuration->get('Ext.Core.ShopSystem.Countries'))) {
            return $configuration->get('Ext.Core.ShopSystem.Countries')->getData();
        }

        if (!empty($configuration->get('shop.shipping.countries'))) {
            return $configuration->get('shop.shipping.countries')->getData();
        }

        return [];
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getShopcart(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    ): \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart
    {
        return $shopcart;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
    ): Response
    {
        if (!empty($this->getConfig('productId'))) {

            // Fetch product
            $product = $productRepository->fetchById($this->getConfig('productId'));
        }

        return new \Frootbox\View\Response([
            'product' => $product ?? null,
        ]);
    }
}
