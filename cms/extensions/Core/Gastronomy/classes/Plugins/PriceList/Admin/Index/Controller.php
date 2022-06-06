<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Admin\Index;

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
    public function ajaxListEntryDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));

        $listEntry->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => 'tr[data-listentry="' . $listEntry->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function ajaxListEntryUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));

        // Update list entry
        $listEntry->setTitle($post->get('title'));
        $listEntry->addConfig([
            'price' => $post->get('price'),
            'unit' => $post->get('unit')
        ]);

        $listEntry->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxModalListEntryEditAction(
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries $listEntriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch list entry
        $listEntry = $listEntriesRepository->fetchById($get->get('listEntryId'));
        $view->set('listEntry', $listEntry);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): \Frootbox\Admin\Controller\Response
    {
        d($post);
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