<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Addresses\Admin\Partials\AddressList;

use Frootbox\Admin\Controller\Response;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering(
        \Frootbox\Db\Db $db,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        if (!empty($this->getData('repository', true))) {
            $addressesRepository = $db->getRepository($this->getData('repository', true));
        }

        // Fetch addresses
        $addresses = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->getData('plugin')->getId(),
            ],
            'order' => [ 'orderId DESC'],
        ]);

        return new Response('html', 200, [
            'addresses' => $addresses,
        ]);
    }
}
