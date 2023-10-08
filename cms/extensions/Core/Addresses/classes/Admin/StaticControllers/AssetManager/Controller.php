<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Admin\StaticControllers\AssetManager;

use Frootbox\Admin\Controller\Response;
use Frootbox\Config\Config;

class Controller extends \Frootbox\Admin\Controller\AbstractControllerStatic
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
    public function ajaxCreate(
        \Frootbox\Db\Db $db,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        $post->require([ 'title' ]);

        if (!empty($get->get('repository'))) {
            $addressesRepository = $db->getRepository($get->get('repository'));
        }

        $rowClass = $addressesRepository->getClass();

        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        // Compose address
        $address = new $rowClass([
            'pluginId' => $plugin->getId(),
            'pageId' => $plugin->getPageId(),
            'title' => $post->get('title'),
            'visibility' => (DEVMODE ? 2 : 1),
            'className' => $rowClass,
        ]);

        // Persist new address
        $address = $addressesRepository->persist($address);

        return new Response('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#addressesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Admin\Partials\AddressList\Partial::class, [
                    'highlight' => $address->getId(),
                    'plugin' => $plugin,
                    'repository' => $get->get('repository'),
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxDelete(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        // Delete address
        $address->delete();

        // Fetch plugin
        $plugin = $contentElements->fetchById($address->getPluginId());

        return new Response('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#addressesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Admin\Partials\AddressList\Partial::class, [
                    'plugin' => $plugin,
                    'repository' => $get->get('repository'),
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalCompose(

    ): Response
    {

        return new Response('html', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxModalEdit(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $configuration,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        return new Response('html', 200, [
            'address' => $address,
            'configuration' => $configuration,
        ]);
    }

    /**
     *
     */
    public function ajaxSwitchVisibility(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));
        $address->visibilityPush();

        return new Response('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-asset="' . $address->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2',
            ],
            'addClass' => [
                'selector' => '.visibility[data-asset="' . $address->getId() . '"]',
                'className' => $address->getVisibilityString(),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return Response
     */
    public function ajaxUpdate(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        $address->setTitle($post->get('title'));

        $address->setStreet($post->get('street'));
        $address->setStreetNumber($post->get('number'));

        $address->setCity($post->get('city'));
        $address->setZipcode($post->get('zipcode'));
        $address->setCountry($post->get('country'));

        $address->setEmail($post->get('email'));
        $address->setPhone($post->get('phone'));

        $address->setLat($post->get('lat'));
        $address->setLng($post->get('lng'));

        $address->save();

        return new Response('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '[data-address="' . $address->getId() . '"] .title',
                'html' => $post->get('title'),
            ],
        ]);
    }
}
