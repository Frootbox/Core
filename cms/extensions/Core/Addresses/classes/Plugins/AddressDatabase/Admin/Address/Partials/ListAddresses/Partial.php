<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListAddresses;

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
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    )
    {
        // Fetch addresses
        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->getData('plugin')->getId()
            ],
        ]);

        $view->set('addresses', $result);
    }
}