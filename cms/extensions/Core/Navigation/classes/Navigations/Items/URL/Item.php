<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\URL;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('url'))) {
            return '#unconfigured-navigation-item';
        }

        $url = $this->getConfig('url');

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
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $url = $post->get('url');
        $url = str_replace(SERVER_PATH_PROTOCOL . 'edit/', '', $url);
        $url = str_replace(SERVER_PATH_PROTOCOL, '', $url);
        $url = str_replace(SERVER_PATH . 'edit/', '', $url);

        if (SERVER_PATH != '/') {
            $url = str_replace(SERVER_PATH, '', $url);
        }

        $this->addConfig([
            'url' => $url,
        ]);
    }
}
