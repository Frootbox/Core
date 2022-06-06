<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListUsers;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Persistence\Repositories\ItemConnections $connections,
        \Frootbox\Persistence\Repositories\Users $userRepository,
    ): Response
    {
        // Obtain address
        $address = $this->getData('address');

        $result = $connections->getEntitiesByBase($userRepository, $address);

        return new Response('html', 200, [
            'xusers' => $result,
        ]);
    }
}