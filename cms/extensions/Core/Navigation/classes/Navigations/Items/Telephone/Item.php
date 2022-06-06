<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\Telephone;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('number'))) {
            return '#unconfigured-navigation-item';
        }

        $number = $this->getConfig('number');

        if ($number[0] == '+') {
            $number = '00' . substr($number, 1);
        }

        if (preg_match('#^0[1-9]#', $number)) {
            $number = '0049' . substr($number, 1);
        }

        $number = preg_replace('#[^a-z\d]#i', '', $number);

        $url = 'tel:' . $number;

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
            'number' => $this->getTitle(),
        ]);

        $this->save();
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'pageId' => $post->get('pageId'),
            'number' => $post->get('number'),
        ]);
    }
}
