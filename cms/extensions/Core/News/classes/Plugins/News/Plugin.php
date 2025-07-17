<?php
/**
 * @noinspection SqlNoDataSourceInspection
 */

namespace Frootbox\Ext\Core\News\Plugins\News;

use Frootbox\Ext\Core\Images\Viewhelper\References;
use Frootbox\Http\Interfaces\ResponseInterface;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    protected $publicActions = [
        'index',
        'showArticle',
        'showCategory',
        'archive',
        'ajaxSearch',
    ];

    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articleRepository
     * @return void
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articleRepository,
    ): void
    {
        // Fetch articles
        $articles = $articleRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $articles->map('delete');
    }

    /**
     * @param \DI\Container $container
     * @param \Frootbox\Persistence\AbstractRow $ancestor
     * @return void
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        $articlesRepository = $container->get(\Frootbox\Ext\Core\News\Persistence\Repositories\Articles::class);
        $articles = $articlesRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ],
        ]);

        foreach ($articles as $article) {

            $newArticle = $article->duplicate();
            $newArticle->setPluginId($this->getId());
            $newArticle->setPageId($this->getPage()->getId());
            $newArticle->setAlias(null);
            $newArticle->save();

            $cloningMachine->cloneContentsForElement($newArticle, $article->getUidBase());
        }
    }

    /**
     *
     */
    public function getArticles(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articleRepository,
        $limit = 100,
        $order = null,
        $skip = null,
        $page = 1,
        $tagsExclude = [],
    ): \Frootbox\Db\Result
    {

        switch ($this->getConfig('sorting')) {

            default:
            case 'DateStartDesc':
                $order = 'dateStart DESC';
                break;

            case 'DateStartAsc':
                $order = 'dateStart ASC';
                break;
        }

        if (!empty($tagsExclude)) {

            $sql = 'SELECT
                SQL_CALC_FOUND_ROWS
                a.*
            FROM
                assets a
            WHERE
                a.className = :className AND
                a.id NOT IN (SELECT itemId FROM tags WHERE itemClass = :className AND tag = :tag) AND
                a.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . '
            ORDER BY
                ' . $order . '
            LIMIT 
                ' . ($limit * $page - $limit) . ',' . $limit . '
            ';

            $articles = $articleRepository->fetchByQuery($sql, [
                'className' => \Frootbox\Ext\Core\News\Persistence\Article::class,
                'tag' => $tagsExclude[0],
            ]);

            $articles->setItemsPerPage($limit);
            $articles->getTotal();
        }
        else {

            if ($order === null) {



            }
            $where = [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\LessOrEqual('dateStart', date('Y-m-d H:i:s')),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
            ];

            if (!empty($skip)) {
                foreach ($skip as $articleId) {
                    $where[] = new \Frootbox\Db\Conditions\NotEqual('id', $articleId);
                }
            }

            // Fetch articles
            $articles = $articleRepository->fetch([
                'calcFoundRows' => true,
                'where' => $where,
                'order' => [ $order ],
                'limit' => $limit,
                'page' => $page,
            ]);
        }

        return $articles;
    }

    /**
     *
     */
    public function getArticlesByYear (
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        $limit = 100
    )
    {

        // Fetch articles
        $result = $articles->fetch([
            'calcFoundRows' => true,
            'where' => [
                'pluginId' => $this->getId()
            ],
            'order' => [ 'dateStart DESC' ],
            'limit' => $limit
        ]);

        $list = [ ];

        foreach ($result as $article) {

            $year = substr($article->getDateStart(), 0, 4);

            if (!isset($list[$year])) {
                $list[$year] = [
                    'year' => $year,
                    'date' => $article->getDateStart(),
                    'articles' => [ ]
                ];
            }

            $list[$year]['articles'][] = $article;
        }


        // Rebase array indices
        $list = array_values($list);

        return $list;
    }

    /**
     *
     */
    public function getAvailableTags(array $parameters = null): \Frootbox\Db\Result
    {
        // Obtain tags repository
        $tagsRepository = $this->getDb()->getRepository(\Frootbox\Persistence\Repositories\Tags::class);

        $payload = [
            'className' => \Frootbox\Ext\Core\News\Persistence\Article::class,
        ];

        $sql = 'SELECT 
            COUNT(t.id) as count, 
            t.tag as tag
        FROM
            tags t,
            assets a
        WHERE
            SUBSTR(t.tag, 1, 1) != "_" AND
            t.itemClass = :className AND
            a.className = t.itemClass AND
            a.id = t.itemId AND
            a.visibility >= ' . (IS_EDITOR ? 1 : 2) . '       
            ';

        if (!empty($parameters['exclude'])) {

            $sql .= ' AND t.tag NOT IN ( ';
            $comma = '';

            foreach ($parameters['exclude'] as $index => $tag) {
                $sql .= $comma . ':tag_' . $index;
                $comma = ', ';

                $payload['tag_' . $index] = $tag;
            }

            $sql .= ' ) ';
        }

        $sql .= ' GROUP BY
            t.tag
        ORDER BY        
            t.tag ASC';

        $result = $tagsRepository->fetchByQuery($sql, $payload);

        return $result;
    }

    /**
     *
     */
    public function ajaxSearchAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articleRepository,
    ): \Frootbox\View\ResponseView
    {
        if ($this->hasAttribute('tags')) {
            $articles = $articleRepository->fetchByTags($this->getAttribute('tags'), [
                'order' => [ 'dateStart DESC' ],
                'mode' => 'matchOne',
            ]);
        }

        return new \Frootbox\View\ResponseView([
            'articles' => $articles ?? [],
        ]);
    }

    /**
     *
     */
    public function ajaxShowArticleAction (
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles
    ): \Frootbox\View\ResponseView
    {
        // Fetch article
        $article = $articles->fetchById($this->getAttribute('articleId'));

        return new \Frootbox\View\ResponseView([
            'article' => $article,
        ]);
    }

    /**
     *
     */
    public function indexAction()
    {

    }

    /**
     *
     */
    public function jumpNextAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository
    )
    {
        // Fetch article
        $article = $articlesRepository->fetchById($this->getAttribute('articleId'));

        // Fetch next
        $where = [
            'pluginId' => $this->getId(),
            new \Frootbox\Db\Conditions\NotEqual('id', $article->getId()),
            new \Frootbox\Db\Conditions\LessOrEqual('dateStart', $article->getDateStart()),
            new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
        ];

        $next = $articlesRepository->fetchOne([
            'where' => $where,
            'order' => [ 'dateStart DESC' ],
        ]);

        return new \Frootbox\View\ResponseRedirect($next->getUri());
    }

    /**
     * @param \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @return \Frootbox\View\Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function showArticleAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\View\Engines\Interfaces\Engine $view
    ): Response
    {
        /**
         * Fetch article
         * @var \Frootbox\Ext\Core\News\Persistence\Article $article
         */
        $article = $articles->fetchById($this->getAttribute('articleId'));

        if (!$article->isVisible()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new \Frootbox\View\Response([
            'article' => $article,
        ]);
    }

    /**
     *
     */
    public function showCategoryAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Categories $categoryRepository,
    ): \Frootbox\View\Response
    {
        // Fetch category
        $category = $categoryRepository->fetchById($this->getAttribute('categoryId'));

        return new \Frootbox\View\Response([
            'category' => $category,
        ]);
    }
}
