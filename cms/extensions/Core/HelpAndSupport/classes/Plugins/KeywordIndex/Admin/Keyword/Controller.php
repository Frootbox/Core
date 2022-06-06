<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Keyword;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Create new keyword
        $keyword = $keywordsRepository->insert(new \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Keyword([
            'title' => $post->get('title'),
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId()
        ]));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#keywordsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Keyword\Partials\ListKeywords\Partial::class, [
                    'highlight' => $keyword->getId(),
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     * Delete keyword
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch keyword
        $keyword = $keywordsRepository->fetchById($get->get('keywordId'));

        $keyword->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#keywordsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Keyword\Partials\ListKeywords\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('plain');
    }
}