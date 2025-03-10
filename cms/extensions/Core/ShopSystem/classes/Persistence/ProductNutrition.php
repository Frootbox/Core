<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class ProductNutrition extends \Frootbox\Persistence\AbstractRow implements \Iterator
{
    protected $table = 'shop_products_nutrition';
    protected $model = Repositories\ProductsNutrition::class;

    private array $keys = [];
    private int $index = 0;

    public function getAdditionForKey(string $key): ?array
    {
        if ($key == 'calorificValue') {
            return [
                'key' => 'CalorificValueKiloJoule',
                'value' => round($this->getCalorificValue() * 4.1868),
                'precision' => 0,
                'unit' => 'KiloJoule',
            ];
        }

        return null;
    }

    public function getCalorificValue(): ?float
    {
        $value = parent::getCalorificValue();

        if ($value === null) {
            return null;
        }

        return $value / 1000;
    }

    public function getCarbohydrates(): ?float
    {
        $value = parent::getCarbohydrates();

        if ($value === null) {
            return null;
        }

        return $value / 1000;
    }

    public function getCarbohydratesOfWhichSugar(): ?float
    {
        $value = parent::getCarbohydratesOfWhichSugar();

        if ($value === null) {
            return null;
        }

        return $value / 1000;
    }

    public function getKeys(): array
    {
        $keys = $this->data;

        unset($keys['id'], $keys['date'], $keys['updated'], $keys['productId']);

        $keys = array_keys(array_filter($keys));

        $this->keys = $keys;

        return $keys;
    }

    public function getPrecisionForKey(string $key): int
    {
        switch ($key) {
            case 'calorificValue':
                return 0;

            default:
                return 2;
        }
    }

    public function getProtein(): ?float
    {
        $value = parent::getProtein();

        if ($value === null) {
            return null;
        }

        return $value / 1000;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getUnitForKey(string $key): ?string
    {
        switch ($key) {
            case 'calorificValue':
                return 'Kilocalories';

            case 'carbohydrates':
            case 'carbohydratesOfWhichSugar':
                return 'Gramm';

            default:
                return null;
        }
    }

    public function setCalorificValue(float $value): void
    {
        parent::setCalorificValue($value * 1000);
    }

    public function setCarbohydrates(float $value): void
    {
        parent::setCarbohydrates($value * 1000);
    }

    public function setCarbohydratesOfWhichSugar(float $value): void
    {
        parent::setCarbohydratesOfWhichSugar($value * 1000);
    }

    public function setProtein(?float $value): void
    {
        if ($value !== null) {
            $value *= 1000;
        }

        parent::setProtein($value);
    }

    public function current(): mixed
    {
        $key = $this->key();
        $getter = 'get' . ucfirst($key);

        return [
            'key' => ucfirst($key),
            'value' => $this->$getter(),
            'unit' => $this->getUnitForKey($key),
            'precision' => $this->getPrecisionForKey($key),
            'addition' => $this->getAdditionForKey($key),
        ];
    }

    public function next(): void
    {
        ++$this->index;
    }

    public function key(): mixed
    {
        return $this->keys[$this->index];
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->keys[$this->index]);
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->getKeys();
        $this->index = 0;
    }
}
