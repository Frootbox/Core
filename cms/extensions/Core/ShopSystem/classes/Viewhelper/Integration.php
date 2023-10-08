<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Viewhelper;

class Integration extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getCouponForUid' => [

        ]
    ];

    /**
     * @return \Frootbox\Ext\Core\ShopSystem\IntegrationInterface
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    protected function getIntegration(): ?\Frootbox\Ext\Core\ShopSystem\Integrations\IntegrationInterface
    {
        $configuration = $this->container->get(\Frootbox\Config\Config::class);

        if (!empty($configuration->get('Ext.Core.ShopSystem.BookingIntegration'))) {

            $className = $configuration->get('Ext.Core.ShopSystem.BookingIntegration');

            return $this->container->get($className);
        }

        return null;
    }

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function canTransferBookingAction(

    ): bool
    {
        $delegator = $this->container->get(\Frootbox\Ext\Core\ShopSystem\Integrations\Delegator::class);

        return $delegator->canTransferBooking();
    }
}
