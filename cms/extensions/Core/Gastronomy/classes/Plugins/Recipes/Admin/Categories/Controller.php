<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Admin\Categories;

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
     * Show categories entries
     */
    public function ajaxPanelListRecipesAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Gastronomy\Plugins\Recipes\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($get->get('categoryId'));

        return self::getResponse('plain', 200, [
            'category' => $category
        ]);
    }


    /**
     *
     */
    public function ajaxContactAddAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {

        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));


        // Fetch contact
        $contact = $contacts->fetchById($post->get('contactId'));


        // Connect contact to category
        $category->connectItem($contact);


        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#contactsCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Categories\Partials\ContactsList\Partial::class, [
                    'category' => $category,
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }


    /**
     *
     */
    public function ajaxContactDisconnectAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {

        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));


        // Fetch contact
        $contact = $contacts->fetchById($get->get('contactId'));


        // Connect contact to category
        $category->disconnectItem($contact);


        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#contactsCategoryListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Categories\Partials\ContactsList\Partial::class, [
                    'category' => $category,
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
