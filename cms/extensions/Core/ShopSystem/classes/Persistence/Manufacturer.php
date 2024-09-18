<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Manufacturer extends \Frootbox\Persistence\AbstractAsset
{
    protected $model = Repositories\Manufacturers::class;

    /**
     * Generate alias
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle(),
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showManufacturer',
                'manufacturerId' => $this->getId(),
            ]),
        ]);
    }

    /**
     *
     */
    public function getProducts(array $options = null): \Frootbox\Db\Result
    {
        $productsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);

        return $productsRepository->fetch([
            'where' => [
                'manufacturerId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2))
            ]
        ]);
    }
}
