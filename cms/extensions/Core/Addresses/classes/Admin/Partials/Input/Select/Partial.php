<?php

/**
 * 
 */

namespace Frootbox\Ext\Core\Addresses\Admin\Partials\Input\Select;

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
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function onBeforeRendering(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Fetch addresses
        $addresses = $addressesRepository->fetch([
            'where' => [
                'className' => $this->getData('className'),
                'pluginId' => $this->getData('pluginId'),
            ],
            'order' => [ 'orderId DESC'],
        ]);

        return new Response(body: [
            'addresses' => $addresses,
        ]);
    }
}
