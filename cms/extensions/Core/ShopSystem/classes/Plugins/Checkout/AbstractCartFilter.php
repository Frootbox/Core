<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

abstract class AbstractCartFilter
{
    public function __construct(
        protected \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    )
    { }
}
