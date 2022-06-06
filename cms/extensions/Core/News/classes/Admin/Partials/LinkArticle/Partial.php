<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\News\Admin\Partials\LinkArticle;

use Frootbox\Db\Conditions\Like;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
    public function ajaxSearch(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Serach articles
        $articles = $articlesRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\Like('title', '%' . $get->get('q') . '%')
            ]
        ]);

        $result = [];

        foreach ($articles as $article) {

            $result[] =  [
                'label' => $article->getTitle(),
                'articleId' => $article->getId(),
                'alias' => $article->getUri(),
            ];
        }

        return new \Frootbox\Admin\Controller\Response('json', 200, [
            'result' => $result
        ]);
    }

    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\Pages $pages
    )
    {

    }
}
