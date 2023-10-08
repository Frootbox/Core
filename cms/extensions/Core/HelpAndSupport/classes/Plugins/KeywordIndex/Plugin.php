<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    protected $publicActions = [
        'index',
        'showKeyword',
    ];

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
     * @return array
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getKeywordsByInitial(): array
    {
        // Fetch keywords
        $keywordsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords::class);
        $keywords = $keywordsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => [ 'title ASC' ]
        ]);

        $list = [];

        foreach ($keywords as $keyword) {

            $initial = strtoupper(mb_substr($keyword->getTitle(), 0, 1));

            if (!isset($list[$initial])) {
                $list[$initial] = [];
            }

            $list[$initial][] = $keyword;
        }

        return $list;
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
