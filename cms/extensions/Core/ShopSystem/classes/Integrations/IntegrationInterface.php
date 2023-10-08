<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Integrations;

interface IntegrationInterface
{
    /**
     * @return bool
     */
    public function canTransferBooking(): bool;

    public function transferBooking(\Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking): void;
}
