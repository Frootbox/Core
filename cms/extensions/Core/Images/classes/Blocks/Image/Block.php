<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Blocks\Image;

class Block extends \Frootbox\Persistence\Content\Blocks\Block
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
}
