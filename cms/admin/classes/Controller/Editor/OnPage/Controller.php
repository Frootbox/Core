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

        if (!empty($post->get('ImportUrl'))) {

            $result = [];

            // Get the HTML content
            $html = @file_get_contents($post->get('ImportUrl'));

            // Load HTML into DOM
            $doc = new \DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTML($html);
            libxml_clear_errors();

            $xpath = new \DOMXPath($doc);

            // Extract title
            $nodes = $xpath->query('//title');
            if ($nodes->length > 0) {
                $result['title'] = trim($nodes->item(0)->nodeValue);
            }

            // Extract meta description
            $nodes = $xpath->query('//meta[@name="description"]/@content');
            if ($nodes->length > 0) {
                $result['description'] = trim($nodes->item(0)->nodeValue);
            }

            // Extract meta keywords
            $nodes = $xpath->query('//meta[@name="keywords"]/@content');
            if ($nodes->length > 0) {
                $result['keywords'] = trim($nodes->item(0)->nodeValue);
            }

            $alias->addConfig([
                'seo' => [
                    'title' => $result['title'] ?? null,
                    'keywords' => $result['keywords'] ?? null,
                    'description' => $result['description'] ?? null,
                ],
            ]);
        }
        else {

            $alias->addConfig([
                'seo' => [
                    'title' => $post->get('seoTitle'),
                    'keywords' => $post->get('metaKeywords'),
                    'description' => $post->get('metaDescription'),
                ],
            ]);
        }

        $alias->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
            'setFields' => [
                [
                    'selector' => '#metaDescription',
                    'value' => $alias->getConfig('seo.description'),
                ],
                [
                    'selector' => '#seoTitle',
                    'value' => $alias->getConfig('seo.title'),
                ],
            ],
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
