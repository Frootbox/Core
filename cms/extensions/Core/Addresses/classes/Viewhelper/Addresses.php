<?php
/**
 *
 */


namespace Frootbox\Ext\Core\Addresses\Viewhelper;

class Addresses extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getByPluginId' => [
            'pluginId',
        ],
        'getByTag' => [
            'tag',
        ],
    ];

    /**
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return \Frootbox\Db\Result
     */
    public function getAddressesAction(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): \Frootbox\Db\Result
    {
        $result = $addressesRepository->fetch();

        return $result;
    }

    /**
     * @param string $tag
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressRepository
     * @return \Frootbox\Db\Result
     */
    public function getByTagAction(
        string $tag,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressRepository,
    ): \Frootbox\Db\Result
    {
        return $addressRepository->fetchByTag($tag);
    }

    public function getByPluginIdAction(
        int $pluginId,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressRepository,
    )
    {

        $result = $addressRepository->fetch([
            'where' => [
                'pluginId' => $pluginId,
            ],
        ]);

        return $result;
    }
}
