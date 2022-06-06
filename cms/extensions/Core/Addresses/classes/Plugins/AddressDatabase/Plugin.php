<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Addresses\Plugins\AddressDatabase;

use DI\Container;
use Frootbox\View\Response;
use Frootbox\View\ResponseJson;


class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showAddress'
    ];

    /**
     *
     */
    public function getAddresses(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository,
    ): ?\Frootbox\Db\Result
    {
        $minVisibility = $session->isLoggedIn() ? 1 : 2;

        // Fetch addresses
        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', $minVisibility),
            ],
          //  'order' => [ 'title ASC' ],
        ]);

        return $result;
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getTags(
        \Frootbox\Persistence\Repositories\Tags $tagsRepository
    )
    {
        // Fetch tags
        $result = $tagsRepository->fetch([
            'where' => [
                'itemClass' => \Frootbox\Ext\Core\Addresses\Persistence\Address::class,
            ],
            'order' => [ 'tag ASC' ],
        ]);

        $list = [];

        foreach ($result as $tag) {
            $list[$tag->getTag()] = $tag->getTag();
        }

        return $list;
    }

    /**
     *
     */
    public function ajaxAddressAction(
        \DI\Container $container,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($this->getAttribute('addressId'));

        return new ResponseJson([
            'address' => $address->getData(),
            'html' => $container->call([ $this, 'renderHtml' ], [
                'action' => 'ajaxAddress',
                'variables' => [
                    'address' => $address,
                ],
            ]),
        ]);
    }

    /**
     *
     */
    public function ajaxAddressListAction(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {

        $lat = $get->get('lat');
        $lon = $get->get('lng');

        $sf = 3.14159 / 180; // scaling factor
        $er = 6350; // earth radius in miles, approximate

        $sql = 'SELECT
                *,
                (ACOS(SIN(lat * ' . $sf . ') * SIN(' . $lat . ' * ' . $sf . ') + COS(lat * ' . $sf . ') * COS(' . $lat . ' * ' . $sf . ') * COS((lng - ' . $lon . ') * ' . $sf . ')) * ' . $er . ') as distance
            FROM
                locations 
            WHERE 
                pluginId = ' . $this->getId() . '
            ORDER BY
                ACOS(SIN(lat * ' . $sf . ') * SIN(' . $lat . ' * ' . $sf . ') + COS(lat * ' . $sf . ') * COS(' . $lat . ' * ' . $sf . ') * COS((lng - ' . $lon . ') * ' . $sf . '))';

        $result = $addressesRepository->fetchByQuery($sql);

        $html = $container->call([ $this, 'renderHtml' ], [
            'action' => 'ajaxAddressList',
            'variables' => [
                'addresses' => $result,
            ],
        ]);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse' ]);

        return new ResponseJson([
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function showAddressAction(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($this->getAttribute('addressId'));

        return new Response([
            'address' => $address,
        ]);
    }
}
