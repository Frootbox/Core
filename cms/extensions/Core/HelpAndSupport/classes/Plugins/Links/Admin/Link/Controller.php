<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Link;

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
    public function ajaxModalComposeAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links,
        \Frootbox\Admin\View $view
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch link
        $link = $links->fetchById($get->get('linkId'));
        $view->set('link', $link);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Validate required input
        $post->require([ 'url' ]);

        $url = $post->get('url');


        // Check protocol
        if (substr($url, 0, 4) == 'www.') {
            $url = 'http://' . $url;
        }

        // Insert new link
        $link = $links->insert(new \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Link([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => ($post->get('title') ?? $post->get('url')),
            'config' => [
                'url' => $url
            ]
        ]));

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#linksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Link\Partials\ListLinks\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $link->getId()
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch link
        $link = $links->fetchById($get->get('linkId'));
        $link->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#linksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Link\Partials\ListLinks\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links
    )
    {
        // Get orderId
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $rowId) {

            // Fetch question
            $link = $links->fetchById($rowId);
            $link->setOrderId($orderId--);
            $link->save();
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Persistence\Repositories\Links $links,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'url' ]);

        // Fetch link
        $link = $links->fetchById($get->get('linkId'));

        $link->setTitle($post->get('title'));
        $link->addConfig([
            'url' => $post->get('url')
        ]);

        // Set tags
        $link->setTags($post->get('tags'));

        $link->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#linksReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Links\Admin\Link\Partials\ListLinks\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $link->getId()
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}