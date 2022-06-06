<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Development\Apps\Migration\Versions;

class V000004 extends \Frootbox\Ext\Core\Development\Apps\Migration\AbstractVersion
{
    protected $steps = [
        'fixShopCategoryConnectionModels'
    ];

    /**
     *
     */
    public function fixShopCategoryConnectionModels(
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): void
    {
        // Fetch connection aliases
        $result = $aliasesRepository->fetch([
            'where' => [
                'itemModel' => 'Frootbox\Persistence\Repositories\CategoriesConnections'
            ]
        ]);

        foreach ($result as $alias) {
            $alias->setItemModel('Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\CategoriesConnections');
            $alias->save();
        }
    }
}
