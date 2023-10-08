<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\Language;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        return '/';
    }

    public function hasAutoItems(): bool
    {
        return true;
    }

    /**
     *
     */
    public function getAutoItems(
        \Frootbox\Config\Config $configuration,
    ): ?\Frootbox\Db\Result
    {
        if (empty($configuration->get('i18n.languages'))) {
            return null;
        }

        $items = new \Frootbox\Db\Result([], $this->getDb());
        $items->setClassName(\Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy::class);

        foreach ($configuration->get('i18n.languages') as $language) {

            $items->push(new \Frootbox\Ext\Core\Navigation\Navigations\Items\Dummy([
                'href' => ($language == 'de-DE') ? SERVER_PATH : SERVER_PATH . substr($language, 0, 2),
                'title' => substr($language, 0, 2),
                'additionalClasses' => substr($language, 0, 2),
            ]));
        }

        return $items;
    }


    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function getTitle()
    {
        if (empty($_SESSION['frontend']['language'])) {
            $_SESSION['frontend']['language'] = 'de-DE';
        }

        return substr($_SESSION['frontend']['language'], 0, 2);
    }

    /**
     *
     */
    public function isActive(array $parameters = null): bool
    {
        if (empty($parameters['page']) or empty($this->config['pageId'])) {
            return false;
        }

        $page = $parameters['page'];

        if ($this->config['pageId'] == $page->getId()) {
            return true;
        }

        if (!empty($parameters['inheritActiveFromeParent']) and $this->config['pageId'] == $page->getParentId()) {
            return true;
        }

        // Check left and right
        return false;

        d($parameters);
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'pageId' => $post->get('pageId'),
            'anchor' => $post->get('anchor'),
            'showChildren' => $post->get('showChildren'),
        ]);
    }
}
