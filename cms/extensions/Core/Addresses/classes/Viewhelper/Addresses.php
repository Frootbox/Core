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
    ];

    /**
     *
     */
    public function getAddressesAction(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    )
    {
        $result = $addressesRepository->fetch();

        return $result;
    }

    public function getByPluginIdAction(
        int $pluginId,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    )
    {

        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $pluginId,
            ],
        ]);

        return $result;
    }
}
