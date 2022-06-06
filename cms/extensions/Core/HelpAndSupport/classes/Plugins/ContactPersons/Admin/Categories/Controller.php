<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Categories;

use Frootbox\Admin\Controller\Response;

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
     * Show categories contacts
     */
    public function ajaxPanelContactsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories
    ): Response
    {
        // Fetch category
        $category = $categories->fetchById($get->get('categoryId'));

        // Fetch all available contacts
        $result = $contacts->fetch([
            // 'where' => [ 'pluginId' => $this->plugin->getId() ]
        ]);


        return self::getResponse('plain', 200, [
            'category' => $category,
            'contacts' => $result,
        ]);
    }

    /**
     *
     */
    public function ajaxContactAddAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
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
    public function ajaxContactDisconnectAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
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
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\CategoriesConnections $connectionsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $connectionId) {

            $connection = $connectionsRepository->fetchById($connectionId);
            $connection->setOrderId($orderId--);
            $connection->save();
        }

        return self::getResponse('json', 200, []);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
