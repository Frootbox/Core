<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Navigation\Navigations\Items\LanguageBinary;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        $languages = $this->configuration->get('i18n.languages');
        $l1 = $languages[0] ?? null;
        $l2 = $languages[1] ?? null;

        $targetLanguage = $_SESSION['frontend']['language'] == $l1 ? $l2 : $l1;

        if ($targetLanguage == 'de-DE') {
            return '/';
        }
        else {
            return '/en';
        }
    }

    public function hasAutoItems(): bool
    {
        return false;
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
        if (defined('IS_BACKEND')) {
            return parent::getTitle();
        }

        if (empty($_SESSION['frontend']['language'])) {
            $_SESSION['frontend']['language'] = 'de-DE';
        }

        $languages = $this->configuration->get('i18n.languages');
        $l1 = $languages[0] ?? null;
        $l2 = $languages[1] ?? null;

        $targetLanguage = $_SESSION['frontend']['language'] == $l1 ? $l2 : $l1;

        return substr($targetLanguage, 0, 2);
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
