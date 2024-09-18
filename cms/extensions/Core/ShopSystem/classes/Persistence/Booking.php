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
    public function getCountry(): ?string
    {
        if (!empty($this->getConfig('billing.country'))) {
            return $this->getConfig('billing.country');
        }

        if (!empty($this->getConfig('personal.country'))) {
            return $this->getConfig('personal.country');
        }

        return null;
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

        $productRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);

        foreach ($this->getConfig('products') as $itemData) {

            $product = null;

            try {
                $product = $productRepository->fetchById($itemData['productId']);
            }
            catch ( \Exception $e ) {
                // Ignore probably delted product
            }

            $list[] = new \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem($itemData, $product);
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
    public function getPaymentData(string $key = null)
    {
        $data = $this->getConfig('payment.data');

        if ($key === null) {
            return $data ?? [];
        }

        return $data[$key] ?? null;
    }

    /**
     * @return \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
     */
    public function getPaymentMethod(): \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
    {
        // Generate method class
        $className = $this->getConfig('payment.methodClass');

        // Create payment method
        $paymentMethod = new $className;

        if (!empty($this->getConfig('payment'))) {
            $paymentMethod->setPaymentData($this->getConfig('payment'));
        }

        return $paymentMethod;
    }

    /**
     * @return \Frootbox\Ext\Core\ShopSystem\Persistence\PickupLocation|null
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function getSelfpickupAddress(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\PickupLocation
    {
        if (empty($this->getConfig('shipping.selfpickupAddressId'))) {
            return null;
        }

        // Fetch address
        $repository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\PickupLocation::class);
        return $repository->fetchById($this->getConfig('shipping.selfpickupAddressId'));
    }

    public function getSelfPickupAddressNote(): ?string
    {
        $address = $this->getSelfpickupAddress();

        if (empty($address)) {
            return null;
        }

        $uid = $address->getUid('text-shipping');

        $repository = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\Texts::class);

        $text = $repository->fetchByUid($uid);

        return $text->getText();
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
    public function getTotal(): float
    {
        $total = (float) 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getAmount() * $item->getPriceFinal();
        }

        $total += $this->getShipping();

        foreach ($this->getCoupons() as $cdata) {
            $total -= $cdata['redeemedValue'];
        }

        return $total;
    }

    /**
     *
     */
    public function getTotalNet(): float
    {
        $total = (float) 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getAmount() * $item->getPrice();
        }

        $total += $this->getShipping();

        foreach ($this->getCoupons() as $cdata) {
            $total -= $cdata['redeemedValue'];
        }

        return $total;
    }

    /**
     *
     */
    public function getVat(): ?string
    {
        if (!empty($this->getConfig('billing.vat'))) {
            return $this->getConfig('billing.vat');
        }

        if (!empty($this->getConfig('personal.vat'))) {
            return $this->getConfig('personal.vat');
        }

        return null;
    }
}
