<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 * @date 2020-06-23
 */

namespace Frootbox\Admin\Controller\Editor\OnPage;

use Frootbox\Admin\Controller\Response;
use Frootbox\Persistence\Repositories\Pages;

/**
 *
 */
class Controller extends \Frootbox\Admin\Controller\AbstractController
{
    /**
     *
     */
    public function ajaxUpdateSeo(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        // Fetch alias
        $alias = $aliasesRepository->fetchById($get->get('aliasId'));

        $alias->addConfig([
            'seo' => [
                'title' => $post->get('seoTitle'),
                'keywords' => $post->get('metaKeywords'),
                'description' => $post->get('metaDescription'),
            ],
        ]);

        $alias->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.'
        ]);
    }

    /**
     *
     */
    public function ajaxIndex(
        \Frootbox\Http\Get $get
    ): Response
    {
        return self::getResponse('json', 200, [
            'title' => 'Seiten-Einstellungen',
            'html' => $this->render()
        ]);
    }

    /**
     *
     */
    public function ajaxIndexSeo(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Repositories\Aliases $aliasesRepository
    ): Response
    {
        if (!empty($get->get('aliasId'))) {

            // Fetch alias
            $alias = $aliasesRepository->fetchById($get->get('aliasId'));
        }

        // Fetch page
        $page = $pagesRepository->fetchById($get->get('pageId'));

        return self::getResponse('json', 200, [
            'title' => 'SEO-Einstellungen',
            'html' => $this->render(null, [
                'alias' => $alias ?? null,
                'page' => $page
            ]),
        ]);
    }


}
