<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    /**
     *
     */
    public function getKeywords(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch keywords
        $result = $keywordsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);

        return $result;
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
    public function showKeywordAction(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordsRepository
    ) {
        // Fetch keyword
        $keyword = $keywordsRepository->fetchById($this->getAttribute('keywordId'));
        $view->set('keyword', $keyword);
    }
}
