<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Languages\Plugins\AutoRedirect;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    protected function getPageByAcceptedLanguage(\Frootbox\Persistence\Repositories\Pages $pagesRepository, string $acceptedLanguagesString ): \Frootbox\Persistence\Page
    {
        $acceptedLanguages = preg_split('/,\s*/', $acceptedLanguagesString);

        // Redirect to first child if no language is given
        if (empty($acceptedLanguages)) {
            return $this->getPage()->getFirstChild();
        }

        // Redirect to first child if language string couldnt be parsed
        if (!preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
            return $this->getPage()->getFirstChild();
        }

        // Fetch children
        $children = $this->getPage()->getChildrenVisible();

        foreach ($matches[1] as $desiredLanguage) {

            foreach ($children as $page) {
                if ($desiredLanguage == $page->getLanguage()) {
                    return $page;
                }
            }

            foreach ($children as $page) {
                if ($desiredLanguage == explode('-', $page->getLanguage())[0]) {
                    return $page;
                }
            }
        }

        // Return first child if no language matched
        return $this->getPage()->getFirstChild();
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Persistence\Repositories\Pages $pagesRepository
    ): Response
    {
        $target = $this->getPageByAcceptedLanguage($pagesRepository, $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        return new \Frootbox\View\ResponseRedirect($target->getUri());
    }
}
