<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group;

use Frootbox\Admin\View;

class Controller extends \Frootbox\Admin\AbstractPluginController {
    
    /**
     *
     */
    public function getPath ( ) : string {
        
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxModalComposeAction ( ) {

        return self::response('plain');
    }


    /**
     *
     */
    public function ajaxModalEditAction (
        \Frootbox\Admin\View $view,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups
    )
    {
        // Fetch group
        $group = $groups->fetchById($get->get('groupId'));

        $view->set('group', $group);

        return self::response('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));


        // Insert new group
        $group = $groups->insert(new \Frootbox\Ext\Core\Forms\Persistence\Group([
            'pageId' => $plugin->getPageId(),
            'pluginId' => $plugin->getId(),
            'title' => $post->get('title')
        ]));


        return self::response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group\Partials\ListGroups::class, [
                    'highlight' => $group->getId(),
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }


    /**
     *
     */
    public function ajaxDeleteAction (

        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Persistence\Repositories\ContentElements $contentElements,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp

    ) {


        // Fetch plugin
        $plugin = $contentElements->fetchById($get->get('pluginId'));


        // Fetch group
        $group = $groups->fetchById($get->get('groupId'));

        $group->delete();


        return self::response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group\Partials\ListGroups::class, [
                    'highlight' => $group->getId(),
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository
    )
    {
        $orderId = count($get->get('groups')) + 1;

        foreach ($get->get('groups') as $groupId) {

            $group = $groupsRepository->fetchById($groupId);

            $group->setOrderId($orderId--);
            $group->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.'
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups
    )
    {
        // Fetch group
        $group = $groups->fetchById($get->get('groupId'));

        $group->setData($post->get('group'));
        $group->save();


        return self::response('json', 200, [
            'replace' => [
                'selector' => 'h4 span[data-group="' . $group->getId() . '"]',
                'html' => $group->getTitle()
            ],
            'modalDismiss' => true
        ]);
    }

    
    /**
     * 
     */
    public function indexAction ( ) {
                        
        return self::response();
    }
}