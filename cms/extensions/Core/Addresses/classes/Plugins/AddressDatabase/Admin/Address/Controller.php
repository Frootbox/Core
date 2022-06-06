<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require(['title']);

        $config = [];

        if (!empty($this->plugin->getConfig('noAddressDetailPage'))) {
            $config['noAddressDetailPage'] = true;
        }

        // Build up address
        $address = new \Frootbox\Ext\Core\Addresses\Persistence\Address([
            'title' => $post->get('title'),
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPage()->getId(),
            'config' => $config,
            'visibility' => (DEVMODE ? 2 : 1),
        ]);

        // Insert new address
        $address = $addressesRepository->insert($address);

        // Log action
        $this->log('AddressCreate', [
            $address->getId(),
            $address->getTitle(),
            get_class($address),
        ]);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#addressesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListAddresses\Partial::class, [
                    'highlight' => $address->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        $address->delete();

        // Log action
        $this->log('AddressDelete', [
            $address->getId(),
            $address->getTitle(),
            get_class($address),
        ]);

        return self::getResponse('json', 200, [
            'fadeOut' => 'tr[data-address="' . $address->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalComposeOpeningTimeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        return self::getResponse('plain', 200, [
            'address' => $address,
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeUserConnectionAction(
        \Frootbox\Persistence\Repositories\Users $userRepository,
    ): Response
    {
        // Fetch users
        $users = $userRepository->fetch();

        return self::getResponse('plain', 200, [
            'users' => $users,
        ]);
    }

    /**
     *
     */
    public function ajaxOpeningTimeCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\OpeningTimes $openingTimesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        // Compose opening time
        $openingTime = new \Frootbox\Ext\Core\Addresses\Persistence\OpeningTime([
            'parentId' => $address->getId(),
            'visibility' => 2,
            'config' => [
                'timeFrom' => $post->get('timeFrom'),
                'timeTo' => $post->get('timeTo'),
                'days' => $post->get('days'),
            ],
        ]);

        $openingTimesRepository->insert($openingTime);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#openingtimesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListOpeningtimes\Partial::class, [
                    'highlight' => $openingTime->getId(),
                    'address' => $address,
                    'plugin' => $this->plugin,
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxOpeningTimeDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\OpeningTimes $openingTimesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        // Fetch opening time
        $openingTime = $openingTimesRepository->fetchById($get->get('openingTimeId'));
        $openingTime->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#openingtimesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListOpeningtimes\Partial::class, [
                    'address' => $address,
                    'plugin' => $this->plugin,
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Get orderId
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $addressId) {

            $address = $addressesRepository->fetchById($addressId);
            $address->setOrderId($orderId--);
            $address->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxSwitchVisibilityAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        $address->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-address="' . $address->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2'
            ],
            'addClass' => [
                'selector' => '.visibility[data-address="' . $address->getId() . '"]',
                'className' => $address->getVisibilityString()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        $address->setTitle($post->get('title'));
        $address->setAddition($post->get('addition'));
        $address->setStreet($post->get('street'));
        $address->setStreetNumber($post->get('streetNumber'));
        $address->setCity($post->get('city'));
        $address->setZipcode($post->get('zipcode'));
        $address->setCountry($post->get('country'));

        $address->setEmail($post->get('email'));
        $address->setPhone($post->get('phone'));
        $address->setMobile($post->get('mobile'));
        $address->setFax($post->get('fax'));

        $address->setUrl($post->get('url'));
        $address->setInstagram($post->get('instagram'));
        $address->setFacebook($post->get('facebook'));

        // Set tags
        $address->setTags($post->get('tags'));

        $address->save();


        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateLocationAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        $address->setLng($post->get('lng'));
        $address->setLat($post->get('lat'));
        $address->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUserConnectionCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Users $userRepository,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
        \Frootbox\Persistence\Repositories\ItemConnections $connections,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        // Fetch user
        $user = $userRepository->fetchById($post->get('userId'));

        // Connect address
        $item = $connections->connect($user, $address);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#userReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListUsers\Partial::class, [
                    'highlight' => $item->getId(),
                    'address' => $address,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUserConnectionDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\ItemConnections $connectionsRepository,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        // Fetch connection
        $connection = $connectionsRepository->fetchById($get->get('connectionId'));

        $connection->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#userReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase\Admin\Address\Partials\ListUsers\Partial::class, [
                    'address' => $address,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($get->get('addressId'));

        return self::getResponse('html', 200, [
            'address' => $address,
            'config' => $config,
        ]);
    }

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
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}