<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

class Shopcart
{
    protected ?array $personal = [];
    protected ?array $shipping = [];
    protected ?array $billing = [];
    protected $payment = [];
    protected $items = [];
    protected $data = [];
    protected $coupons = [];
    protected ?string $orderNumber = null;
    protected ?string $uniqueId = null;

    protected $db;
    protected $config;
    protected \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository;

    /**
     *
     */
    public function __construct(
        \Frootbox\Db\Db $db,
        \Frootbox\Config\Config $config,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
    )
    {
        $this->config = $config;
        $this->db = $db;
        $this->productRepository = $productRepository;

        if (!empty($_SESSION['cart']['products'])) {
            $this->items = &$_SESSION['cart']['products'];
        }

        if (!empty($_SESSION['cart']['personal'])) {
            $this->personal = &$_SESSION['cart']['personal'];
        }

        if (!empty($_SESSION['cart']['shipping'])) {
            $this->shipping = &$_SESSION['cart']['shipping'];
        }

        if (!empty($_SESSION['cart']['billing'])) {
            $this->billing = &$_SESSION['cart']['billing'];
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

            if (empty($options['forcePriceGross'])) {
                $priceGross = $product->getPriceGross();
                $price = $product->getPrice();
            }
            else {
                $priceGross = $options['forcePriceGross'];
                $price = round($priceGross / (1 + ($product->getTaxrate() / 100)), 2);
            }

            $uri = !empty($options['uri']) ? $options['uri'] : $product->getUri();


            $item = [
                'key' => $key,
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'amount' => 0,
                'amountMax' => $options['amountMax'] ?? null,
                'boundTo' => $options['boundTo'] ?? null,
                'price' => $price,
                'priceGross' => $priceGross,
                'taxRate' => $product->getTaxrate(),
                'uri' => $uri,
                'shippingId' => (array_key_exists('shippingId', $options) ? $options['shippingId'] : $product->getShippingId()),
                'type' => (empty($options['type']) ? 'Product' : $options['type']),
                'customNote' => $options['customNote'] ?? null,
                'isAmountFixed' => !empty($options['isAmountFixed']),
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
        if (!empty($_SESSION['cart']['products'][$key]['boundTo'])) {

            $boundItem = $this->getItem($_SESSION['cart']['products'][$key]['boundTo']);
            $_SESSION['cart']['products'][$key]['amount'] = $boundItem->getAmount();
        }
        else {

            $amount = !empty($options['amount']) ? (int) $options['amount'] : 1;
            $_SESSION['cart']['products'][$key]['amount'] += $amount;
        }


        $this->reloadItems();

        return $this->getItem($key);
    }

    /**
     *
     */
    public function clearItems(): void
    {
        unset($_SESSION['cart']['products']);
        $this->items = [];
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
    public function getBilling(string $attribute): ?string
    {
        return $this->billing[$attribute] ?? (string) null;
    }

    /**
     * @return array
     */
    public function getBillingData(): array
    {
        return $this->billing;
    }

    /**
     *
     */
    public function getConfig(): \Frootbox\Config\Config
    {
        return $this->config;
    }

    /**
     * @return \Frootbox\Db\Db
     */
    public function getDb(): \Frootbox\Db\Db
    {
        return $this->db;
    }

    /**
     * @param string $key
     * @return ShopcartItem
     */
    public function getItem(string $key): ShopcartItem
    {
        return new ShopcartItem($this->items[$key] ?? null);
    }

    /**
     * @param int $productId
     * @return ShopcartItem|null
     */
    public function getItemByProductId(int $productId): ?\Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem
    {
        foreach ($this->getItems() as $item) {
            if ($item->getProductId() == $productId) {
                return $item;
            }
        }

        return null;
    }

    /**
     *
     */
    public function getItemCount(): int
    {
        if (empty($_SESSION['cart']['products'])) {
            return 0;
        }

        $productCount = 0;

        foreach ($_SESSION['cart']['products'] as $item) {
            $productCount += $item['amount'];
        }

        return $productCount;
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

            if (!empty($itemData['productId'])) {
                $product = $this->productRepository->fetchById($itemData['productId']);
            }

            $items[] = new ShopcartItem($itemData, $product ?? null);
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

    public function getNote(): ?string
    {
        return $this->personal['note'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
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
     * @param string $paymentMethodClass
     * @return void
     */
    public function getPaymentMethodClass(): ?string
    {
        return $_SESSION['cart']['paymentmethod']['methodClass'] ?? null;
    }

    /**
     *
     */
    public function getPaymentMethodId(): ?string
    {
        if (empty($_SESSION['cart']['paymentmethod']['methodClass'])) {
            return null;
        }

        preg_match('#\\\\PaymentMethods\\\\([a-z]+)\\\\Method$#i', $_SESSION['cart']['paymentmethod']['methodClass'], $match);

        return $match[1];
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
     * @return \Frootbox\Ext\Core\ShopSystem\Persistence\PickupLocation|null
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function getSelfpickupAddress(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\PickupLocation
    {
        if (empty($this->shipping['selfpickupAddressId'])) {
            return null;
        }

        // Fetch address
        $repository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\PickupLocation::class);
        return $repository->fetchById($this->shipping['selfpickupAddressId']);
    }

    /**
     *
     */
    public function getShipping(string $attribute): ?string
    {
        return $this->shipping[$attribute] ?? (string) null;
    }

    /**
     * @return array
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
        $costs = 0;
        $extraCosts = 0;

        if (!empty($_SESSION['cart']['shipping']['costsExtra'])) {
            foreach ($_SESSION['cart']['shipping']['costsExtra'] as $xExtraCosts) {
                $extraCosts += (float) $xExtraCosts['costs'];
            }
        }

        if ($this->getShipping('type') == 'selfPickup') {
            return $extraCosts;
        }

        $repository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts::class);

        foreach ($this->getItems() as $item) {

            if (empty($item->getShippingId())) {
                continue;
            }

            // Fetch shippingcosts
            $shippingcosts = $repository->fetchById($item->getShippingId());

            if ($shippingcosts->isApplicableToCertainProduct()) {
                continue;
            }

            $newcosts = $shippingcosts->getCosts($item, $this);

            if ($newcosts === null) {
                return null;
            }

            if ($newcosts > $costs or $costs === null) {
                $costs = $newcosts;
            }
        }

        return $costs + $extraCosts;
    }

    public function getShippingCostsExtra(): array
    {
        return $_SESSION['cart']['shipping']['costsExtra'] ?? [];
    }

    /**
     *
     */
    public function getShippingMethod(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts
    {
        if ($this->getShipping('type') == 'selfPickup') {
            return null;
        }

        $repository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts::class);

        $costs = 0;
        $method = null;

        foreach ($this->getItems() as $item) {

            if (empty($item->getShippingId())) {
                continue;
            }

            // Fetch shippingcosts
            $shippingcosts = $repository->fetchById($item->getShippingId());

            if ($shippingcosts->isApplicableToCertainProduct()) {
                continue;
            }

            $newcosts = $shippingcosts->getCosts($item, $this);

            if ($newcosts === null) {
                return null;
            }

            if ($newcosts > $costs or $costs === null) {
                $costs = $newcosts;
                $method = $shippingcosts;
            }
        }

        return $shippingcosts ?? null;
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
                    'tax' => 0,
                    'net' => 0,
                ];
            }

            $sections[$taxrate]['tax'] += $item->getTax();
            $sections[$taxrate]['total'] += $item->getPriceGrossFinal() * $item->getAmount();
            $sections[$taxrate]['net'] += ($item->getPriceGrossFinal() * $item->getAmount()) - $item->getTax();

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
    public function getTotalNetItems(): float
    {
        $total = 0.0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotalNet();
        }

        return $total;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        if (!empty($this->uniqueId)) {
            return $this->uniqueId;
        }

        if (empty($_SESSION['cart']['uniqueId'])) {
            $_SESSION['cart']['uniqueId'] = md5(microtime(true));
        }

        $this->uniqueId = $_SESSION['cart']['uniqueId'];

        return $this->uniqueId;
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
    public function reloadItems(): void
    {
        if (!empty($_SESSION['cart']['products'])) {
            $this->items = &$_SESSION['cart']['products'];
        }
    }

    /**
     *
     */
    public function setBilling(?array $billingData): void
    {
        $this->billing = $billingData;

        $_SESSION['cart']['billing'] = $this->billing;
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
        $this->items[$key]['shippingExtra'] = $item->getShippingExtra();

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
     * @param string|null $note
     * @return void
     */
    public function setNote(?string $note): void
    {
        $this->personal['note'] = $note;
    }

    /**
     * @param string $orderNumber
     * @return void
     */
    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return void
     */
    public function setPaid(): void
    {
        $this->payment['state'] = 'Paid';
        $this->payment['paidAt'] = date('Y-m-d H:i:s');
    }

    /**
     * @deprecated
     */
    public function setPayed(): void
    {
        $this->setPaid();
    }

    /**
     *
     */
    public function setPaymentData(array $paymentData): void
    {
        $this->payment['data'] = $paymentData;
    }

    /**
     * @param string $paymentMethodClass
     * @return void
     */
    public function setPaymentMethodClass(string $paymentMethodClass): void
    {
        $_SESSION['cart']['paymentmethod']['methodClass'] = $paymentMethodClass;
    }

    /**
     * @param array $personalData
     * @return void
     */
    public function setPersonal(array $personalData): void
    {
        $this->personal = $personalData;

        $_SESSION['cart']['personal'] = $this->personal;
    }

    /**
     *
     */
    public function setShipping(?array $shippingData): void
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

    /**
     *
     */
    public function setShippingCostsExtra(string $key, float $costs, string $description = null): void
    {
        $_SESSION['cart']['shipping']['costsExtra'][$key] = [
            'costs' => $costs,
            'description' => $description,
        ];
    }

    public function updateShippingCosts(): void
    {
        foreach ($this->getItems() as $item) {

            $shippingCosts = $item->getProduct()->getShippingCosts();

            if (empty($shippingCosts)) {
                continue;
            }

            if ($shippingCosts->isApplicableToCertainProduct()) {

                $costs = $shippingCosts->getCosts($item, $this);

                $item->setShippingExtra($costs);
                $this->setItem($item);
            }
        }
    }
}
