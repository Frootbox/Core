<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Admin\Keyword;

use JetBrains\PhpStorm\NoReturn;

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

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordRepository
     * @return \Frootbox\Admin\Controller\Response
     */
    #[NoReturn] public function jumpToEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\KeywordIndex\Persistence\Repositories\Keywords $keywordRepository,
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch keyword
        $keyword = $keywordRepository->fetchById($get->get('keywordId'));

        header('Location: ' . $keyword->getUriEdit());
        exit;
    }
}