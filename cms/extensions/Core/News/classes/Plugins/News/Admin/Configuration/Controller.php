<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Plugins\News\Admin\Configuration;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository
     * @return Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\News\Persistence\Repositories\Articles $articlesRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'sorting' => $post->get('sorting'),
            'noArticleDetailPage' => $post->get('noArticleDetailPage'),
            'urlPrefixFullDate' => $post->get('urlPrefixFullDate'),
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
                'urlPrefixFullDate' => $post->get('urlPrefixFullDate'),
            ]);

            $article->save();
        }

        return self::getResponse('json', 200, [

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
