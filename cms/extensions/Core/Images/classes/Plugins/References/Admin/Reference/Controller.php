<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Reference;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Interfaces\ResponseInterface;

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
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Create new reference
        $reference = $referencesRepository->insert(new \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Reference([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title'),
            'visibility' => (DEVMODE ? 2 : 1),
            'dateStart' => date('Y-m-d H:i:s'),
        ]));

        // Fix sorting
        $result = $referencesRepository->fetch([
            'where' => [
                'pageId' => $this->plugin->getPageId(),
                'pluginId' => $this->plugin->getId(),
            ],
            'order' => [ 'orderId DESC'],
        ]);

        $orderId = $result->getCount() + 1;

        foreach ($result as $xref) {
            $xref->setOrderId(--$orderId);
            $xref->save();
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#referencesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Reference\Partials\ListReferences::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $reference->getId(),
                ])
            ],
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxCloneAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Admin\Front $front
    ): Response
    {
        // Fetch reference
        $oldReference = $referencesRepository->fetchById($get->get('referenceId'));

        // Clone reference
        $reference = clone $oldReference;
        $reference->setTitle($reference->getTitle() . ' KOPIE');
        $reference = $referencesRepository->insert($reference);

        // Clone contents
        $cloningMachine->cloneContentsForElement($reference, $oldReference->getUidBase());

        $front->flash('Die Referenz wurde kopiert.');

        return self::getResponse('json', 200, [
            'triggerLink' => $this->plugin->getAdminUri('Reference', 'details', [ 'referenceId' => $reference->getId() ])
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));

        $reference->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#referencesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Reference\Partials\ListReferences::class, [
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxMoveAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));

        // Fetch plugin
        $plugin = $contentElements->fetchById($post->get('pluginId'));

        $reference->setPluginId($plugin->getId());
        $reference->setPageId($plugin->getPageId());
        $reference->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalMoveAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));

        // Fetch reference plugins
        $result = $contentElements->fetch([
            'where' => [
                'className' => \Frootbox\Ext\Core\Images\Plugins\References\Plugin::class,
            ],
        ]);

        return self::getResponse('plain', 200, [
            'plugins' => $result,
            'reference' => $reference,
        ]);
    }

    public function ajaxSearchAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ):Response
    {
        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#referencesReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Reference\Partials\ListReferences::class, [
                    'plugin' => $this->plugin,
                    'keyword' => $post->get('keyword'),
                ]),
            ],
            'success' => 'Die Suche wurde ausgeführt.',
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    )
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $referenceId) {

            $reference = $referencesRepository->fetchById($referenceId);
            $reference->setOrderId($orderId--);
            $reference->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Validate requried input
        $post->requireOne([ 'title', 'titles' ]);

        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));

        // Update reference
        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');
        $titles = !empty($post->get('titles')) ? array_filter($post->get('titles')) : [];

        $reference->setTitle($title);
        $reference->setDateStart($post->get('dateStart') . ' ' . $post->get('dateStartTime'));
        $reference->setLocationId($post->get('locationId'));

        $reference->unsetConfig('titles');
        $reference->addConfig([
            'url' => $post->get('url'),
            'titles' => $titles,
            'forceReferencesDetailPage' => $post->get('forceReferencesDetailPage'),
        ]);
        $reference->save();

        // Set tags
        $reference->setTags($post->get('tags'));

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Fetch reference
        $reference = $referencesRepository->fetchById($get->get('referenceId'));
        $view->set('reference', $reference);

        return self::getResponse();
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }
}
