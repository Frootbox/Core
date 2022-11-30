<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking\PaymentMethods;

abstract class AbstractPaymentMethod
{
    /**
     *
     */
    public function getMethodName(): string
    {
        preg_match('#\\\([a-z0-9]+)\\\PaymentMethod$#i', get_class($this), $match);

        return $match[1];
    }
}
