<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

class ShopcartItem
{
    protected $key;
    protected $productId;
    protected $title;
    protected $description;
    protected $uri;
    protected $amount;
    protected $price;
    protected $priceGross;
    protected $taxrate;
    protected $equipment;
    protected $shippingId;
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

    /**
     *
     */
    public function __construct(array $itemData = null)
    {
        $this->key = $itemData['key'] ?? null;
        $this->productId = $itemData['productId'] ?? null;
        $this->title = $itemData['title'] ?? null;
        $this->uri = $itemData['uri'] ?? null;
        $this->amount = $itemData['amount'] ?? null;
        $this->price = $itemData['price'] ?? null;
        $this->priceGross = $itemData['priceGross'] ?? null;
        $this->equipment = $itemData['equipment'] ?? [];
        $this->taxrate = $itemData['taxRate'] ?? null;
        $this->shippingId = $itemData['shippingId'] ?? null;
        $this->itemNumber = $itemData['itemNumber'] ?? null;
        $this->customNote = $itemData['customNote'] ?? null;
        $this->minimumAge = $itemData['minimumAge'] ?? null;
        $this->boundTo = $itemData['boundTo'] ?? null;
        $this->variantId = $itemData['variantId'] ?? null;
        $this->additionalText = $itemData['additionalText'] ?? null;

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
     *
     */
    public function getTax()
    {
        return round(($this->getPriceGross() * $this->getAmount()) - (($this->getPriceGross() * $this->getAmount()) / (1 + $this->getTaxrate() / 100)), 2);
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
        return (float) $this->getAmount() * $this->getPriceGross();
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
}
