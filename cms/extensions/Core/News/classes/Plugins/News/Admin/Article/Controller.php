<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Article;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
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
    public function ajaxModalComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCategorySetAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch article
        $article = $articlesRepository->fetchById($get->get('articleId'));

        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        if ($get->get('state')) {
            $category->connectItem($article);
        }
        else {
            $category->disconnectItem($article);
        }

        $article->addConfig([
            'touched' => $_SERVER['REQUEST_TIME'],
        ]);
        $article->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required fields
        $post->requireOne([ 'title', 'titles' ]);

        // Insert multiple articles
        if (!empty($post->get('titles'))) {
            $titles = array_reverse(explode("\n", $post->get('titles')));
        }
        else {
            $titles = [ $post->get('title') ];
        }

        $date = new \DateTime();
        $date->modify('-' . count($titles) . ' second');

        foreach ($titles as $title) {

            // Insert new article
            $article = $articles->insert(new \Frootbox\Ext\Core\News\Persistence\Article([
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
                'title' => $title,
                'dateStart' => $date->format('Y-m-d H:i:s'),
                'visibility' => (DEVMODE ? 2 : 1),
            ]));

            $date->modify('+1 second');
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#articlesNewReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\News\Plugins\News\Admin\Article\Partials\ListArticles::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $article->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch article
        $article = $articles->fetchById($get->get('articleId'));

        $article->delete();

        return self::response('json', 200, [
            'replace' => [
                'selector' => '#articlesNewReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\News\Plugins\News\Admin\Article\Partials\ListArticles::class, [
                    'plugin' => $this->plugin
                ])
            ],
            'success' => 'Der Artikel wurde gelöscht.'
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articles
    ): Response
    {
        // Validate requried input
        $post->requireOne([ 'title', 'titles' ]);

        // Fetch article
        $article = $articles->fetchById($get->get('articleId'));

        // Parse title
        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        // Parse link
        $link = $post->get('link');
        $link = str_replace(SERVER_PATH_PROTOCOL, '', $link);

        // Update article
        $article->setTitle($title);
        $article->setDateStart($post->get('dateStart') . ' ' . $post->get('timeStart'));

        $article->unsetConfig('titles');
        $article->unsetConfig('noIndividualDetailPage');
        $article->addConfig([
            'titles' => !empty($post->get('titles')) ? array_filter($post->get('titles')) : [],
            'link' => $link,
            'noIndividualDetailPage' => $post->get('noIndividualDetailPage'),
            'source' => $post->get('source'),
            'dateDisplay' => $post->get('dateDisplay'),
        ]);


        $article->save();

        // Set tags
        $article->setTags($post->get('tags'));

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch article
        $article = $articlesRepository->fetchById($get->get('articleId'));

        // Fetch next article
        if ($get->get('target') == 'next') {

            $article = $articlesRepository->fetchOne([
                'where' => [
                    new \Frootbox\Db\Conditions\NotEqual('id', $article->getId()),
                    new \Frootbox\Db\Conditions\LessOrEqual('dateStart', $article->getDateStart()),
                ],
                'order' => [
                    'dateStart DESC'
                ]
            ]);
        }

        // Fetch categories
        $category = $categoriesRepository->fetchOne([
            'where' => [
                'uid' => $this->plugin->getUid('categories'),
                'parentId' => 0
            ]
        ]);

        $categories = $category ? $categoriesRepository->getTree($category->getRootId()) : null;


        return self::getResponse('html', 200, [
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}