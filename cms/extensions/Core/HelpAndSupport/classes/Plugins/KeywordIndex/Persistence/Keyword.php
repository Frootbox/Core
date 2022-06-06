<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence;

class Keyword extends \Frootbox\Persistence\AbstractAsset
{
    use \Frootbox\Http\Traits\UrlSanitize;

    protected $model = Repositories\Keywords::class;

    /**
     * Generate questions alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'payload' => $this->generateAliasPayload([
                'action' => 'showKeyword',
                'keywordId' => $this->getId()
            ])
        ]);
    }
}