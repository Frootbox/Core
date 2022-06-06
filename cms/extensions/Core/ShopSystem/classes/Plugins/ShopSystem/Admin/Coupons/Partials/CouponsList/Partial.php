<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Coupons\Partials\CouponsList;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
    ): void
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch coupons
        $result = $couponsRepository->fetch([
            'where' => [ 'pluginId' => $plugin->getId() ],
            'order' => [ 'title' ]
        ]);

        $view->set('coupons', $result);
    }
}
