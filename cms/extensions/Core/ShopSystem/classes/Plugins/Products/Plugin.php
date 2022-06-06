<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Products;

use Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
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
    public function indexAction(
        Products $productsRepository
    ): Response
    {
        if (!empty($this->getConfig('tags'))) {

            // Fetch products
            $result = $productsRepository->fetchByTags($this->getConfig('tags'));
        }
        else {

            // Fetch products
            $result = $productsRepository->fetch();
        }


        return new \Frootbox\View\Response([
            'products' => $result
        ]);
    }
}
