<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Venues;

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
    public function ajaxModalComposeAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalDetailsAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Http\Get $get
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch venue
        $venue = $venues->fetchById($get->get('venueId'));
        $view->set('venue', $venue);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Create venue
        $venue = $venues->insert(new \Frootbox\Ext\Core\Events\Persistence\Venue([
            'title' => $post->get('title'),
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPage()->getId(),
            'orderId' => 0
        ]));

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#venuesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Venues\Partials\ListVenues\Partial::class, [
                    'highlight' => $venue->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch venue
        $venue = $venues->fetchById($get->get('venueId'));

        // Delete venue
        $venue->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#venuesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Venues\Partials\ListVenues\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Venues $venues,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch venue
        $venue = $venues->fetchById($get->get('venueId'));

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');


        $venue->setTitle($title);

        $venue->unsetConfig('titles');
        $venue->addConfig([
            'titles' => $post->get('titles'),
        ]);

        $venue->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#venuesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Events\Plugins\Schedule\Admin\Venues\Partials\ListVenues\Partial::class, [
                    'highlight' => $venue->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     * 
     */
    public function indexAction(): \Frootbox\Admin\Controller\Response
    {
        return self::getResponse();
    }
}
