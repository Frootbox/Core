<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Authors;

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
    public function ajaxAuthorToggleAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Authors $authorRepository,
    ): Response
    {
        // Fetch author
        $author = $authorRepository->fetchById($get->get('authorId'));

        $author->addConfig([
            'isAuthor' => !$author->getConfig('isAuthor'),
        ]);
        $author->save();

        $response = [
            'success' => 'Die Daten wurden gespeichert.',
        ];

        if ($author->getConfig('isAuthor')) {
            $response['addClass'] = [
                'selector' => '[data-author="' . $author->getId() . '"]',
                'className' => 'text-success',
            ];
        }
        else {
            $response['removeClass'] = [
                'selector' => '[data-author="' . $author->getId() . '"]',
                'className' => 'text-success',
            ];
        }

        return self::getResponse('json', 200, $response);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'sorting' => $post->get('sorting'),
            'noArticleDetailPage' => $post->get('noArticleDetailPage'),
        ]);

        $this->plugin->save();

        // Update articles
        $result = $articlesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $article) {

            $article->addConfig([
                'noArticleDetailPage' => $post->get('noArticleDetailPage'),
            ]);

            $article->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\News\Persistence\Repositories\Authors $authorsRepository,
    ): Response
    {
        // Fetch authors
        $authors = $authorsRepository->fetch();

        return self::getResponse('html', 200, [
            'authors' => $authors,
        ]);
    }
}
