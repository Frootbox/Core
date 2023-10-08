<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\PostalCodesExcludes;

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
    ): void
    {
        // $view->set('shippingcosts', $shippingCostsRepository->fetch());
    }
}
