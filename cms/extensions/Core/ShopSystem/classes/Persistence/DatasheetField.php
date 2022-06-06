<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class DatasheetField extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\DatasheetFields::class;

    /**
     *
     */
    public function delete()
    {
        // Clean up product data
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData::class);

        $result = $repository->fetch([
            'where' => [
                'fieldId' => $this->getId(),
            ],
        ]);

        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getHelpText(): ?string
    {
        $helpText = null;

        switch ($this->getType()) {

            case 'Kilojoule':
                $helpText = 'Eingabe in Kilojoule als ganze Zahl ohne Einheit.';
                break;

            case 'Gram':
                $helpText = 'Eingabe in Gramm als ganze Zahl ohne Einheit.';
                break;
        }

        return $helpText;
    }

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getOptions(): array
    {
        $options = explode("\n", $this->getValueText());

        return array_map('trim', $options);;
    }
    /**
     *
     */
    public function getSection(): string
    {
        return $this->getConfig('section') ?? 'default';
    }

    /**
     *
     */
    public function getSuffix(): ?string
    {
        $suffix = null;

        switch ($this->getType()) {

            case 'Kilojoule':
                $suffix = 'kJ';
                break;

            case 'Gram':
                $suffix = 'g';
                break;
        }

        return $suffix;
    }

    /**
     *
     */
    public function getType(): string
    {
        return $this->getConfig('type') ?? 'Text';
    }

    /**
     *
     */
    public function isOptional(): bool
    {
        return substr($this->getType(), 0, 8) == 'Optional';
    }

    /**
     *
     */
    public function setSection(string $section = null): void
    {
        $this->unsetConfig('section');

        if (!empty($section)) {
            $this->addConfig([
                'section' => $section
            ]);
        }
    }

    /**
     *
     */
    public function setType(string $type): void
    {
        $this->addConfig([
            'type' => $type
        ]);
    }

    /**
     *
     */
    public function getValueDisplay(): ?string
    {
        if (strlen(trim($this->getValueText())) == 0) {
            return null;
        }

        $value = null;

        switch ($this->getType()) {

            case 'Kilojoule':
                $kJ = (int) $this->getValueInt() / 1000;
                $value = $kJ . ' kJ/' . round($kJ / 4.184) . ' kcal';
                break;

            case 'OptionalList':

                $options = explode("\n", $this->getValueText());
                $value = implode(", ", array_map('trim', $options));
                break;

            default:
                $value = $this->getValueText() . ' ' . $this->getSuffix();
        }

        return $value;
    }
}
