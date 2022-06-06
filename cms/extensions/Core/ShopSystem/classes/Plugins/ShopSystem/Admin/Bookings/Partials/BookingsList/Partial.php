<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Bookings\Partials\BookingsList;

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
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
    )
    {
        // Obtain plugin
        $plugin = $this->getData('plugin');

        // Fetch bookings
        $result = $bookingsRepository->fetch([
            'where' => [  ],
            'order' => [ 'date DESC' ]
        ]);

        $view->set('bookings', $result);
    }
}
