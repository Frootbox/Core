<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index;

use \Frootbox\Admin\Controller\Response;

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
    public function ajaxModalDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers,
        \Frootbox\Admin\View $view
    ): Response
    {
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));
        $view->set('teaser', $teaser);
        
        return self::getResponse('plain');
    }

    /**
     * 
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Persistence\Repositories\Pages $pagesRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers
    ): Response
    {
        // Validate required input
        $post->requireOne([ 'multiple', 'title' ]);

        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));

        $titles = !empty($post->get('multiple')) ? explode("\n", $post->get('multiple')) : [ $post->get('title') ];

        foreach ($titles as $title) {

            $config = [];

            $pages = $pagesRepository->fetch([
                'where' => [
                    'title' => $post->get('title'),
                ],
            ]);

            if ($pages->getCount() == 1) {
                $config['redirect']['pageId'] = $pages->current()->getId();
            }

            // Insert new teaser
            $teaser = $teasers->insert(new \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Teaser([
                'pageId' => $plugin->getPageId(),
                'pluginId' => $plugin->getId(),
                'title' => trim($title),
                'config' => $config,
                'visibility' => (DEVMODE ? 2 : 1),
            ]));
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#teaserReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers::class, [
                    'highlight' => $teaser->getId(),
                ]),
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));

        // Delete teaser
        $teaser->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#teaserReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers::class, [
                   // 'highlight' => $child->getId()
                ])
            ],
            'success' => 'Der Datensatz wurde gelöscht.'
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers
    ): Response
    {
        // Get orderId
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $teaserId) {

            $teaser = $teasers->fetchById($teaserId);
            $teaser->setOrderId($orderId--);
            $teaser->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     * 
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Navigation\Plugins\Teaser\Persistence\Repositories\Teasers $teasers,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch teaser
        $teaser = $teasers->fetchById($get->get('teaserId'));

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');
        $teaser->setTitle($title);

        $teaser->unsetConfig('titles');
        $teaser->addConfig([
            'titles' => !empty($post->get('titles')) ? array_filter($post->get('titles')) : [],
            'skipLanguages' => $post->get('skipLanguages'),
        ]);

        if (!$post->get('linkageDeactivated')) {

            $pageId = ((preg_match('#^([0-9]+)$#s', $post->get('targetInput'), $match) or preg_match('#^fbx\:\/\/page:([0-9]+)$#s', $post->get('targetInput'), $match)) ? $match[1] : null);

            if (empty($post->get('searchInput'))) {
                $pageId = null;
            }

            $teaser->addConfig([
                'linkageDeactivated' => false,
                'redirect' => [
                    'target' => str_replace(SERVER_PATH_PROTOCOL, '', $post->get('target')),
                    'email' => $post->get('email'),
                    'intern' => null,
                    'pageId' => $pageId,
                    'article' => [
                        'id' => $post->get('articleId'),
                        'title' => $post->get('articleTitle'),
                    ],
                ],
            ]);
        }
        else {

            $teaser->addConfig([
                'linkageDeactivated' => true,
            ]);
        }

        $teaser->save();
        
        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#teaserReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Navigation\Plugins\Teaser\Admin\Index\Partials\ListTeasers::class, [
                    'highlight' => $teaser->getId(),
                ]),
            ],
            'modalDismiss' => true,
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
