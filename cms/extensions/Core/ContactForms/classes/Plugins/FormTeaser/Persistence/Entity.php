<?php
/**
 *
 */

namespace Frootbox\Ext\PixelFabrikExt\RealEstateBroker\Persistence;

class Property extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\Properties::class;

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showProperty',
                'propertyId' => $this->getId()
            ])
        ]);
    }
}