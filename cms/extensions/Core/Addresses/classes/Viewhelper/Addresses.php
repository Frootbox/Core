<?php
/**
 *
 */


namespace Frootbox\Ext\Core\Addresses\Viewhelper;

class Addresses extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [

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
}
