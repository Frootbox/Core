<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Booking extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Bookings::class;

    /**
     *
     */
    public function getTaxSections(): array
    {
        return $this->getConfig('persistedData.taxSections') ?? [];
    }

    /**
     *
     */
    public function getTax(): float
    {
        $tax = (float) 0;

        foreach ($this->getTaxSections() as $taxData) {
            $tax += $taxData['tax'];
        }

        return $tax;
    }


    /**
     *
     */
    public function getCoupons(): array
    {
        $coupons = $this->getConfig('coupons');

        if (empty($coupons)) {
            return [];
        }

        $couponsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons::class);

        foreach ($coupons as $index => $couponData) {

            try {
                $coupon = $couponsRepository->fetchById($couponData['couponId']);
                $coupons[$index]['coupon'] = $coupon;
            }
            catch ( \Exception $e ) {

            }
        }

        return $coupons;
    }

    /**
     *
     */
    public function getItems(): array
    {
        $list = [];

        foreach ($this->getConfig('products') as $itemData) {

            $list[] = new \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem($itemData);
        }

        return $list;
    }

    /**
     *
     */
    public function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getOrderNumber(): string
    {
        return $this->getConfig('orderNumber');
    }

    /**
     *
     */
    public function getPaymentMethod(): \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
    {
        $className = $this->getConfig('payment.methodClass');

        return new $className;
    }

    /**
     *
     */
    public function getShipping(): float
    {
        return $this->getConfig('persistedData.shippingCosts') ?? 0;
    }

    /**
     *
     */
    public function getTotal(): float
    {
        $total = (float) 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getAmount() * $item->getPriceGross();
        }

        $total += $this->getShipping();

        foreach ($this->getCoupons() as $cdata) {
            $total -= $cdata['redeemedValue'];
        }

        return $total;
     }
}
