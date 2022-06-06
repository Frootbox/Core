<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence;

class ImageMap extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\ImageMaps::class;

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return null;

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle(),
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showPlayspace',
                'playspaceId' => $this->getId(),
            ])
        ]);
    }

    /**
     *
     */
    public function delete()
    {
        // Clean up points
        $this->getPoints()->map('delete');

        return parent::delete();
    }

    /**
     *
     */
    public function getPoints(): \Frootbox\Db\Result
    {
        $pointsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Points::class);

        $result = $pointsRepository->fetch([
            'where' => [
                'parentId' => $this->getId(),
            ],
        ]);

        return $result;
    }
}