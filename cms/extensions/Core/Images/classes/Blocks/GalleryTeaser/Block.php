<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Blocks\GalleryTeaser;

class Block extends \Frootbox\Persistence\Content\Blocks\Block
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function getCategory(): ?\Frootbox\Ext\Core\Images\Persistence\Category
    {
        if (empty($this->getConfig('CategoryId'))) {
            return null;
        }

        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Images\Persistence\Repositories\Categories::class);
        $category = $repository->fetchById($this->getConfig('CategoryId'));

        return $category;
    }
}
