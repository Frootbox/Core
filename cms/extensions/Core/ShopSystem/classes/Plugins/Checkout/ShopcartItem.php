<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

class ShopcartItem
{
    protected ?string $key;
    protected $productId;
    protected $title;
    protected $description;
    protected $uri;
    protected $amount;
    protected ?int $amountMax = null;
    protected $price;
    protected $priceGross;
    protected $taxrate;
    protected $equipment;
    protected $shippingId;
    protected ?float $shippingExtra;
    protected $itemNumber;
    protected $customNote;
    protected $minimumAge;
    protected $boundTo;
    protected $hasOptions = false;
    protected $fieldOptions = [];
    protected $variantId;
    protected $config;
    protected $additionalText;
    protected $noExtraCharge = false;
    protected $hasSurcharge = false;
    protected string $type;
    protected ?array $xdata = null;
    protected bool $isAmountFixed = false;

    protected $product;

    /**
     *
     */
    public function __construct(array $itemData = null, \Frootbox\Ext\Core\ShopSystem\Persistence\Product $product = null)
    {
        $this->product = $product;
        $this->key = $itemData['key'] ?? null;
        $this->productId = $itemData['productId'] ?? null;
        $this->title = $itemData['title'] ?? null;
        $this->uri = $itemData['uri'] ?? null;
        $this->amount = $itemData['amount'] ?? null;
        $this->amountMax = $itemData['amountMax'] ?? null;
        $this->price = $itemData['price'] ?? null;
        $this->priceGross = $itemData['priceGross'] ?? null;
        $this->equipment = $itemData['equipment'] ?? [];
        $this->taxrate = $itemData['taxRate'] ?? null;
        $this->shippingId = $itemData['shippingId'] ?? null;
        $this->shippingExtra = $itemData['shippingExtra'] ?? null;
        $this->itemNumber = $itemData['itemNumber'] ?? null;
        $this->customNote = $itemData['customNote'] ?? null;
        $this->minimumAge = $itemData['minimumAge'] ?? null;
        $this->boundTo = $itemData['boundTo'] ?? null;
        $this->variantId = $itemData['variantId'] ?? null;
        $this->additionalText = $itemData['additionalText'] ?? null;
        $this->type = $itemData['type'] ?? 'Product';

        if (isset($itemData['isAmountFixed'])) {
            $this->isAmountFixed = $itemData['isAmountFixed'];
        }

        if (!empty($itemData['xdata'])) {
            $this->xdata = $itemData['xdata'];
        }

        if (!empty($itemData['hasOptions'])) {
            $this->hasOptions = true;
        }

        if (!empty($itemData['fieldOptions'])) {
            $this->fieldOptions = $itemData['fieldOptions'];
        }

        if (!empty($itemData['noExtraCharge'])) {
            $this->noExtraCharge = true;
        }

        if (!empty($itemData['hasSurcharge'])) {
            $this->hasSurcharge = true;
        }

        $this->config = $itemData['config'] ?? [];
    }

    /**
     *
     */
    public function getAdditionalText(): ?string
    {
        return $this->additionalText;
    }

    /**
     *
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     *
     */
    public function getAmountMax(): int
    {
        return $this->amountMax !== null ? $this->amountMax : 20;
    }

    /**
     *
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     *
     */
    public function getCustomNote(): ?string
    {
        return $this->customNote;
    }

    /**
     *
     */
    public function getEquipment(): array
    {
        return $this->equipment;
    }

    /**
     *
     */
    public function getFieldOptions(): array
    {
        return $this->fieldOptions;
    }

    /**
     *
     */
    public function getFieldOption($groupId): ?int
    {
        foreach ($this->fieldOptions as $option) {
            if ($option['groupId'] == $groupId) {
                return (int) $option['optionId'];
            }
        }

        return null;
    }

    /**
     *
     */
    public function getItemNumber(): ?string
    {
        return $this->itemNumber;
    }

    /**
     *
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     *
     */
    public function getMinimumAge(): ?int
    {
        return $this->minimumAge;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        $price = (float) $this->price;

        return $price;
    }

    /**
     * @return float
     */
    public function getPriceGross(): float
    {
        if ($this->noExtraCharge and !$this->hasSurcharge()) {
            return 0;
        }

        $price = (float) $this->priceGross;

        if (!empty($this->equipment)) {

            foreach ($this->equipment as $equipment) {
                $price += $equipment['priceGross'];
            }
        }

        return $price;
    }

    /**
     *
     */
    public function getPriceGrossFinal(): float
    {
        if (empty($this->shippingExtra)) {
            return $this->getPriceGross();
        }

        return $this->getTotal() / $this->getAmount();
    }

    /**
     *
     */
    public function getPriceFinal(): float
    {
        if (empty($this->shippingExtra)) {
            return $this->getPriceGross();
        }

        return $this->getTotal() / $this->getAmount();
    }

    /**
     *
     */
    public function getProduct(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\Product
    {
        return $this->product;
    }

    /**
     *
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     *
     */
    public function getShippingId(): ?int
    {
        return $this->shippingId;
    }

    /**
     * @return float|null
     */
    public function getShippingExtra(): ?float
    {
        return $this->shippingExtra;
    }

    /**
     *
     */
    public function getTax()
    {
        return round(($this->getPriceGrossFinal() * $this->getAmount()) - (($this->getPriceGrossFinal() * $this->getAmount()) / (1 + $this->getTaxrate() / 100)), 2);
    }

    /**
     *
     */
    public function getTaxrate(): int
    {
        return (int) $this->taxrate;
    }

    /**
     *
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     *
     */
    public function getTotal(): float
    {
        $total = (float) $this->getAmount() * $this->getPriceGross();

        if (!empty($this->shippingExtra)) {
            $total += $this->shippingExtra;
        }

        $perEach = ($total / $this->getAmount()) * 100;
        $perEach = round($perEach) / 100;

        return $perEach * $this->getAmount();
    }

    /**
     *
     */
    public function getTotalNet(): float
    {
        $total = (float) $this->getAmount() * $this->getPrice();

        if (!empty($this->shippingExtra)) {
            $total += $this->shippingExtra;
        }

        return $total;
    }

    /**
     *
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     *
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     *
     */
    public function getVariantId(): ?int
    {
        return $this->variantId;
    }

    /**
     * @return array
     */
    public function getXData(): array
    {
        return $this->xdata ?? [];
    }

    /**
     *
     */
    public function hasSurcharge(): bool
    {
        return $this->hasSurcharge;
    }

    /**
     *
     */
    public function hasOptions(): bool
    {
        return $this->hasOptions;
    }

    /**
     *
     */
    public function isAmountFixed(): bool
    {
        return $this->isAmountFixed;
    }

    /**
     *
     */
    public function isBound(): bool
    {
        return !empty($this->boundTo);
    }

    /**
     *
     */
    public function setAdditionalText(string $additionalText): void
    {
        $this->additionalText = $additionalText;
    }

    /**
     *
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     *
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     *
     */
    public function setShippingExtra(?float $shippingExtra): void
    {
        $this->shippingExtra = $shippingExtra;
    }
}
