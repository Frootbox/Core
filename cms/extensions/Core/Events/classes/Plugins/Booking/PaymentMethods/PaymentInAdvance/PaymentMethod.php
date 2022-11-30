<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\PaymentMethods\PaymentInAdvance;

class PaymentMethod extends \Frootbox\Ext\Core\Events\Plugins\Booking\PaymentMethods\AbstractPaymentMethod
{
    protected ?\Frootbox\Config\Config $config = null;

    /**
     *
     */
    public function __construct(\Frootbox\Config\Config $config)
    {
        $this->config = $config;
    }
}
