<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\Email;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('email'))) {
            return '#unconfigured-navigation-item';
        }

        $url = 'mailto:' . $this->getConfig('email');

        return $url;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function initialize(): void
    {
        $this->addConfig([
            'email' => $this->getTitle(),
        ]);

        $this->save();
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'email' => $post->get('email'),
        ]);
    }
}
