<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Coupon extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Coupons::class;

    protected $redeemedValue = 0;

    /**
     * Delete datasheet
     */
    public function delete()
    {
        // Cleanup fields
        $fields = $this->db->getModel(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);
        $result = $fields->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        $result->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getCode(): string
    {
        return $this->getUidRaw();
    }

    /**
     *
     */
    public function getFields(): \Frootbox\Db\Result
    {
        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        // Fetch fields
        $result = $fieldsRepository->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     * Deactivate alias uris for datasheets
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;
    }

    /**
     *
     */
    public function getRedeemedValue(): float
    {
        return $this->redeemedValue;
    }

    /**
     *
     */
    public function getValue(): float
    {
        return (float) $this->getConfig('value');
    }

    /**
     *
     */
    public function getValueLeft(): float
    {
        return $this->getConfig('remaining') ?? $this->getValue();
    }

    /**
     *
     */
    public function getValuePercent(): int
    {
        return (int) $this->getConfig('valuePercent');
    }

    /**
     *
     */
    public function setRedeemedValue(float $redemeedValue): void
    {
        $this->redeemedValue = $redemeedValue;
    }
}
