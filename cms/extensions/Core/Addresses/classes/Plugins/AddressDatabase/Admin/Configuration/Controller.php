<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Configuration;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noAddressDetailPage' => $post->get('noAddressDetailPage'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noAddressDetailPage' => $post->get('noAddressDetailPage'),
            ]);

            $entity->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::response();
    }
}