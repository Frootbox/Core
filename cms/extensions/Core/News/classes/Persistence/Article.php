<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Persistence;

class Article extends \Frootbox\Persistence\AbstractAsset implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Tags;
    use \Frootbox\Persistence\Traits\Alias{
        \Frootbox\Persistence\Traits\Alias::getUri as getUriFromTrait;
    }

    protected $model = Repositories\Articles::class;

    /**
     *
     */
    public function getCategory(): ?\Frootbox\Ext\Core\News\Persistence\Category
    {
        $sql = 'SELECT
            c.*
        FROM
            categories c,
            assets a,
            categories_2_items x
        WHERE
            x.categoryId = c.id AND
            x.itemId = a.id AND
            x.itemClass = :itemClass AND
            a.id = ' . $this->getId() . ' 
        LIMIT 1';


        $categoryRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\News\Persistence\Repositories\Categories::class);
        $result = $categoryRepository->fetchByQuery($sql , [
            'itemClass' => get_class($this),
        ]);

        if ($result->getCount() == 0) {
            return null;
        }

        return $result->current();
    }

    /**
     *
     */
    public function getDateDisplay(): string
    {
        if (empty($this->getConfig('dateDisplay'))) {
            return 'Default';
        }

        return $this->getConfig('dateDisplay');
    }

    /**
     *
     */
    public function getDateStart(): \Frootbox\Dates\Date
    {
        return new \Frootbox\Dates\Date($this->data['dateStart']);
    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'] ?? [];
    }

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noIndividualDetailPage')) or !empty($this->getConfig('noArticleDetailPage'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showArticle',
                'articleId' => $this->getId()
            ]),
        ]);
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noIndividualDetailPage')) or !empty($this->getConfig('noArticleDetailPage'))) {
            return [];
        }

        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $this->getPageId(),
                    'virtualDirectory' => [
                        $title,
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showArticle',
                        'articleId' => $this->getId()
                    ]),
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [ new \Frootbox\Persistence\Alias([
                    'pageId' => $this->getPageId(),
                    'language' => $this->getLanguage() ?? GLOBAL_LANGUAGE,
                    'virtualDirectory' => [
                        $this->getTitle()
                    ],
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showArticle',
                        'articleId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
    }

    /**
     *
     */
    public function getSource(): ?string
    {
        return $this->getConfig('source');
    }

    /**
     *
     */
    public function getTitleWithoutFallback($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? null;
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->getConfig('link'))) {
            return $this->getConfig('link');
        }

        return $this->getUriFromTrait($options);
    }

    /**
     *
     */
    public function showReadMore(): bool
    {
        if (!empty($this->getConfig('link'))) {
            return true;
        }

        if (!empty($this->getConfig('noArticleDetailPage')) or !empty($this->getConfig('noIndividualDetailPage'))) {
            return false;
        }

        if (!empty($this->getTextByUid('text', [ 'ignoreLanguage' => true ]))) {
            return true;
        }

        if (defined('EDITING')) {
            return true;
        }

        return false;
    }
}