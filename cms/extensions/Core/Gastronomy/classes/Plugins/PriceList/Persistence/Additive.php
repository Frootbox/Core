<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class Additive extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Prices::class;

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
    public function getSymbol(): ?string
    {
        if (empty($this->getConfig('symbol'))) {
            $this->addConfig([
                'symbol' => $this->getOrderId(),
            ]);
            $this->save();
        }

        return $this->getConfig('symbol');
    }
}
