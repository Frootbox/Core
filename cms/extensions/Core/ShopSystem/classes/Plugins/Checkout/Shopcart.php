<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

class Shopcart
{
    protected $personal = [];
    protected $shipping = [];
    protected $payment = [];
    protected $items = [];
    protected $data = [];
    protected $coupons = [];

    protected $db;
    protected $config;

    /**
     *
     */
    public function __construct(
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $config
    )
    {
        $this->config = $config;
        $this->db = $db;

        if (!empty($_SESSION['cart']['products'])) {
            $this->items = &$_SESSION['cart']['products'];
        }

        if (!empty($_SESSION['cart']['personal'])) {
            $this->personal = &$_SESSION['cart']['personal'];
        }

        if (!empty($_SESSION['cart']['shipping'])) {
            $this->shipping = &$_SESSION['cart']['shipping'];
        }

        if (!empty($_SESSION['cart']['paymentmethod'])) {
            $this->payment = &$_SESSION['cart']['paymentmethod'];
        }

        if (!empty($_SESSION['cart']['data'])) {
            $this->data = &$_SESSION['cart']['data'];
        }

        if (empty($_SESSION['cart']['coupons'])) {
            $_SESSION['cart']['coupons'] = [];
        }

        $this->coupons = &$_SESSION['cart']['coupons'];
    }

    /**
     *
     */
    public function addItem(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Product $product,
        array $options = null
    ): ShopcartItem
    {
        // Generate item key
        $key = $product->getId();

        if (!empty($options['variant'])) {
            $variant = $options['variant'];
            $key .= '-' . $variant->getId();
        }

        if (!empty($options['unique'])) {
            $key .= '-' . rand(1000, 9999);
        }

        $key = substr(md5($key), 0, 7);

        // Initialize
        if (empty($_SESSION['cart']['products'][$key])) {

            $item = [
                'key' => $key,
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'amount' => 0,
                'price' => $product->getPrice(),
                'priceGross' => $product->getPriceGross(),
                'taxRate' => $product->getTaxrate(),
                'uri' => $product->getUri(),
                'shippingId' => $product->getShippingId(),
            ];

            if (!empty($options['customNote'])) {
                $item['customNote'] = $options['customNote'];
            }


            if (!empty($options['variant'])) {

                $variant = $options['variant'];

                $item['variantId'] = $variant->getId();
                $item['title'] .= ' (' . $variant->getTitle() . ')';
                $item['priceGross'] = $product->getPriceForVariant($variant);
                $item['price'] = $item['priceGross'] / (1 + ($item['taxRate'] / 100));
            }

            $_SESSION['cart']['products'][$key] = $item;
        }

        // Add equipment to item
        if (!empty($options['equipment'])) {

            $item = $_SESSION['cart']['products'][$key];

            foreach ($options['equipment'] as $equipment) {

                // Fetch product
                $item['equipment'][$equipment->getId()] = [
                    'title' => $equipment->getTitle(),
                    'productId' => $equipment->getId(),
                    'amount' => 1,
                    'price' => $equipment->getPrice(),
                    'priceGross' => $equipment->getPriceGross(),
                    'taxRate' => $equipment->getTaxrate(),
                    'uri' => $equipment->getUri()
                ];
            }

            $_SESSION['cart']['products'][$key] = $item;
        }


        // Increase amount of product
        ++$_SESSION['cart']['products'][$key]['amount'];

        return $this->getItem($key);
    }

    /**
     *
     */
    public function couponDismiss(\Frootbox\Ext\Core\ShopSystem\Persistence\Coupon $coupon): void
    {
        foreach ($this->coupons as $index => $couponId) {

            if ($coupon->getId() == $couponId) {
                unset($this->coupons[$index]);
            }
        }
    }

    /**
     *
     */
    public function couponRedeem(\Frootbox\Ext\Core\ShopSystem\Persistence\Coupon $coupon): void
    {
        if ($coupon->getState() == 'Redeemed') {
            // throw new \Exception('Coupon is already redeemed.');
        }

        $this->coupons[] = $coupon->getId();

        $this->coupons = array_unique($this->coupons);
    }

    /**
     *
     */
    public function dropItemByKey(string $key): void
    {
        if (!empty($this->items[$key])) {

            foreach ($this->items as $item) {

                if (empty($item['boundTo'])) {
                    continue;
                }

                if ($item['boundTo'] == $key) {
                    $this->dropItemByKey($item['key']);
                }
            }

            unset($_SESSION['cart']['products'][$key]);
            unset($this->items[$key]);
        }
    }

    /**
     *
     */
    public function getConfig(): \Frootbox\Config\Config
    {
        return $this->config;
    }

    /**
     *
     */
    public function getItem(string $key): ShopcartItem
    {
        return new ShopcartItem($this->items[$key]);
    }

    /**
     *
     */
    public function getItemCount(): int
    {
        $count = 0;

        foreach ($this->items as $item) {
            $count += $item['amount'] ?? 1;
        }

        return $count;
    }

    /**
     *
     */
    public function getItems(): array
    {
        $items = [];

        foreach ($this->items as $itemData) {

            if (empty($itemData['key'])) {
                continue;
            }

            $items[] = new ShopcartItem($itemData);
        }

        return $items;
    }

    /**
     *
     */
    public function getItemsRaw(): array
    {
        return $this->items;
    }

    /**
     *
     */
    public function getItemsTotal(): float
    {
        $total = 0.0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }

    /**
     *
     */
    public function getNewsletterConsent(): bool
    {
        return $this->data['newsletter']['content'] ?? false;
    }

    /**
     *
     */
    public function getPaymentInfo(): array
    {
        return $this->payment;
    }

    /**
     *
     */
    public function getPaymentMethod(): \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
    {
        if (empty($_SESSION['cart']['paymentmethod']['methodClass'])) {
            return new \Frootbox\Ext\Core\ShopSystem\PaymentMethods\Unknown\Method;
        }

        return new $_SESSION['cart']['paymentmethod']['methodClass'];
    }

    /**
     *
     */
    public function getPaymentData(string $key = null)
    {
        if ($key === null) {
            return $this->payment['data'] ?? [];
        }

        return $this->payment['data'][$key] ?? null;
    }

    /**
     *
     */
    public function getPersonal(string $attribute): ?string
    {
        return $this->personal[$attribute] ?? (string) null;
    }

    /**
     *
     */
    public function getPersonalData(): array
    {
        return $this->personal;
    }

    /**
     *
     */
    public function getRedeemedCoupons(): array
    {
        if (empty($this->coupons)) {
            return [];
        }

        $list = [];
        $itemsTotal = $this->getItemsTotal();

        $couponsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons::class);

        foreach ($this->coupons as $couponId) {

            try {
                $coupon = $couponsRepository->fetchById($couponId);
            }
            catch ( \Frootbox\Exceptions\NotFound $e ) {
                continue;
            }

            if ($coupon->getState() == 'Redeemed') {
                // continue;
            }

            if (!empty($coupon->getValueLeft())) {

                if ($coupon->getValueLeft() <= $itemsTotal) {
                    $redeemedValue = $coupon->getValueLeft();
                }
                else {
                    $redeemedValue = $itemsTotal;
                }

            }
            else {

                // d($itemsTotal);
                $redeemedValue = $itemsTotal * ($coupon->getConfig('valuePercent') / 100);

                $itemsTotal -= $redeemedValue;
            }

            $coupon->setRedeemedValue($redeemedValue);

            $list[] = $coupon;
        }

        return $list;
    }

    /**
     *
     */
    public function getShipping(string $attribute): ?string
    {
        return $this->shipping[$attribute] ?? (string) null;
    }

    /**
     *
     */
    public function getShippingData(): array
    {
        return $this->shipping;
    }

    /**
     *
     */
    public function getShippingCosts(): ?float
    {
        if ($this->getShipping('type') == 'selfPickup') {
            return 0;
        }

        $repository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts::class);

        $costs = 0;

        foreach ($this->getItems() as $item) {

            if (empty($item->getShippingId())) {
                continue;
            }

            $shippingcosts = $repository->fetchById($item->getShippingId());

            $newcosts = $shippingcosts->getCosts($item, $this);

            if ($newcosts === null) {
                return null;
            }

            if ($newcosts > $costs or $costs === null) {
                $costs = $newcosts;
            }
        }

        return $costs;
    }

    /**
     *
     */
    public function getTax(): float
    {
        $tax = 0.0;

        foreach ($this->getItems() as $item) {

            $tax += round(($item->getPriceGross() * $item->getAmount()) - (($item->getPriceGross() * $item->getAmount()) / (1 + $item->getTaxrate() / 100)), 2);
        }

        if ($this->getShippingCosts() > 0) {
            $tax += $this->getShippingCosts() - round($this->getShippingCosts() / (1 + $item->getTaxrate() / 100), 2);
        }

        return $tax;
    }

    /**
     *
     */
    public function getTaxSections()
    {
        $sections = [];
        $lastTaxrate = null;

        foreach ($this->getItems() as $item) {

            $taxrate = $item->getTaxrate();

            if (empty($taxrate)) {
                continue;
            }

            if (!isset($sections[$taxrate])) {
                $sections[$taxrate] = [
                    'taxrate' => $taxrate,
                    'total' => 0,
                    'tax' => 0
                ];
            }

            $sections[$taxrate]['tax'] += $item->getTax();
            $sections[$taxrate]['total'] += $item->getPriceGross() * $item->getAmount();

            $lastTaxrate = $taxrate;
        }

        // Parse coupons
        $coupons = $this->getRedeemedCoupons();

        if (count($coupons)) {

            foreach ($coupons as $coupon) {


                if (!empty($coupon->getValuePercent())) {

                    foreach ($sections as $taxRate => $section) {
                        $couponValue = ($sections[$taxRate]['total'] * (($coupon->getValuePercent()) / 100));

                        $sections[$taxRate]['tax'] -= round(($couponValue) - (($couponValue) / (1 + $taxRate / 100)), 2);
                        $sections[$taxRate]['total'] -= $couponValue;
                    }
                }
                else {

                    if (count($sections) == 1) {
                        $sections[$lastTaxrate]['tax'] -= round(($coupon->getValue()) - (($coupon->getValue()) / (1 + $lastTaxrate / 100)), 2);
                        $sections[$lastTaxrate]['total'] -= $coupon->getRedeemedValue();
                    }
                    else {

                        $couponValue = $coupon->getRedeemedValue();

                        foreach ($sections as $taxrate => $section) {

                            $part = $section['total'] / $this->getTotalItems();
                            $couponValue = $coupon->getRedeemedValue() * $part;

                            $sections[$taxrate]['tax'] -= round($couponValue - ($couponValue / (1 + $taxrate / 100)), 2);
                            $sections[$taxrate]['total'] -= $couponValue;
                        }
                    }
                }
            }
        }

        if ($this->getShippingCosts() > 0 and !empty($sections[$lastTaxrate])) {
            $sections[$lastTaxrate]['tax'] += round(($this->getShippingCosts()) - (($this->getShippingCosts()) / (1 + $lastTaxrate / 100)), 2);
            $sections[$lastTaxrate]['total'] += $this->getShippingCosts();
        }

        return $sections;
    }

    /**
     *
     */
    public function getTotal(): float
    {
        // Get total from items
        $total = $this->getTotalItems();

        // Calculate redeemed coupons
        foreach ($this->getRedeemedCoupons() as $coupon) {
            $total -= $coupon->getRedeemedValue();
        }

        // Calculate shipping costs
        $total += $this->getShippingCosts();

        return round($total, 2);
    }

    /**
     *
     */
    public function getTotalItems(): float
    {
        $total = 0.0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }

    /**
     *
     */
    public function hasProduct(int $productId): bool
    {
        foreach ($this->items as $item) {

            if ($item['productId'] == $productId) {
                return true;
            }

            if (!empty($item['equipment'])) {

                foreach ($item['equipment'] as $xitem) {

                    if ($xitem['productId'] == $productId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     *
     */
    public function setItem(ShopcartItem $item): void
    {
        $key = $item->getKey();

        $this->items[$key]['amount'] = $item->getAmount();
        $this->items[$key]['title'] = $item->getTitle();
        $this->items[$key]['fieldOptions'] = $item->getFieldOptions();
        $this->items[$key]['variantId'] = $item->getVariantId();
        $this->items[$key]['config'] = $item->getConfig();
        $this->items[$key]['additionalText'] = $item->getAdditionalText();
        $this->items[$key]['price'] = $item->getPrice();
        $this->items[$key]['priceGross'] = $item->getPriceGross();
        $this->items[$key]['hasSurcharge'] = $item->hasSurcharge();

        // Check amount of bound items
        $amount = 0;
        $list = [];

        foreach ($this->items as $boundItem) {

            if (empty($boundItem['boundTo'])) {
                continue;
            }

            if ($boundItem['boundTo'] == $item->getKey()) {
                $boundItem['amount'] = $item->getAmount();
                $_SESSION['cart']['products'][$boundItem['key']] = $boundItem;
            }
        }
    }

    /**
     *
     */
    public function setNewsletterConsent(bool $consent): void
    {
        $this->data['newsletter']['content'] = $consent;
    }

    /**
     *
     */
    public function setPayed(): void
    {
        $this->payment['state'] = 'Payed';
        $this->payment['payedAd'] = date('Y-m-d H:i:s');
    }

    /**
     *
     */
    public function setPaymentData(array $paymentData): void
    {
        $this->payment['data'] = $paymentData;
    }

    /**
     *
     */
    public function setPaymentMethodClass(string $paymentMethodClass): void
    {
        $_SESSION['cart']['paymentmethod']['methodClass'] = $paymentMethodClass;
    }

    /**
     *
     */
    public function setPersonal(array $personalData): void
    {
        $this->personal = $personalData;

        $_SESSION['cart']['personal'] = $this->personal;
    }

    /**
     *
     */
    public function setShipping(array $shippingData): void
    {
        $this->shipping = $shippingData;

        $_SESSION['cart']['shipping'] = $this->shipping;
    }

    /**
     *
     */
    public function setShippingCosts($costs)
    {
        $_SESSION['cart']['shipping']['costs'] = $costs;
    }
}
