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
     * @param array $parameters
     * @return \Frootbox\Db\Result|null
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getAddresses(array $parameters = []): ?\Frootbox\Db\Result
    {
        $limit = $parameters['limit'] ?? 1024;

        $order = $parameters['order'] ?? [];


        // Fetch addresses
        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
            'limit' => $limit,
            'order' => $order,
        ]);

        return $result;
    }

    /**
     * @param array $parameters
     * @return array
     */
    public function getAddressesByLetter(array $parameters = []): array
    {
        if (empty($parameters['order'])) {
            $parameters['order'] = [ 'title ASC' ];
        }

        $addresses = $this->getAddresses($parameters);

        $list = [];

        foreach ($addresses as $address) {

            $key = mb_strtolower(mb_substr($address->getTitle(), 0, 1));

            if (!isset($list[$key])) {
                $list[$key] = [];
            }

            $list[$key][] = $address;
        }

        return $list;
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getTags(): array
    {
        // Fetch tags
        $tagsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Tags::class);
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
     * @param \Frootbox\Http\Get $get
     * @param \DI\Container $container
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return \Frootbox\View\Response
     */
    public function ajaxAddressListAction(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        $lat = $get->get('lat');
        $lon = $get->get('lng');

        if (empty($lat) or empty($lon)) {
            http_response_code(422);
            die("Parameter Missing");
        }


        $sf = 3.14159 / 180; // scaling factor
        $er = 6350; // earth radius in miles, approximate

        $sql = 'SELECT
                *,
                (ACOS(SIN(lat * ' . $sf . ') * SIN(' . $lat . ' * ' . $sf . ') + COS(lat * ' . $sf . ') * COS(' . $lat . ' * ' . $sf . ') * COS((lng - ' . $lon . ') * ' . $sf . ')) * ' . $er . ') as distance
            FROM
                locations 
            WHERE 
                pluginId = ' . $this->getId() . ' AND 
                visibility >= ' . (IS_EDITOR ? 1 : 2) . '
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
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return Response
     */
    public function showAddressAction(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): Response
    {
        // Fetch address
        $address = $addressesRepository->fetchById($this->getAttribute('addressId'));

        if (!$address->isVisible()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new Response([
            'address' => $address,
        ]);
    }
}
