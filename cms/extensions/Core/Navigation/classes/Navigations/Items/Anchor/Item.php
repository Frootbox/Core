<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\Anchor;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('anchor'))) {
            return '#unconfigured-navigation-item';
        }

        return SERVER_PATH . '#' . $this->getConfig('anchor');
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'anchor' => $post->get('anchor'),
        ]);
    }
}
