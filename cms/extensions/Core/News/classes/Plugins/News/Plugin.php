<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News;

use Frootbox\Http\Interfaces\ResponseInterface;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    protected $publicActions = [
        'index',
        'showArticle',
        'showCategory',
        'archive',
    ];

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
     *
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
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        $limit = 100,
        $order = null,
        $skip = null,
        $page = 1
    ): \Frootbox\Db\Result
    {
        if ($order === null) {

            switch ($this->getConfig('sorting')) {

                default:
                case 'DateStartDesc':
                    $order = 'dateStart DESC';
                    break;

                case 'DateStartAsc':
                    $order = 'dateStart ASC';
                    break;
            }

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
        $result = $articles->fetch([
            'calcFoundRows' => true,
            'where' => $where,
            'order' => [ $order ],
            'limit' => $limit,
            'page' => $page,
        ]);


        return $result;
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
     *
     */
    public function showArticleAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        // Fetch article
        $article = $articles->fetchById($this->getAttribute('articleId'));
        $view->set('article', $article);
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
