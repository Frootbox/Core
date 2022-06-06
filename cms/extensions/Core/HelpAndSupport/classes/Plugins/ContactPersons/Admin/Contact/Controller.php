<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Contact;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Get;

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
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Persistence\Repositories\CategoriesConnections $connections
    ): Response
    {
        // Fetch contact
        $contact = $contacts->fetchById($get->get('contactId'));
        $view->set('contact', $contact);

        if (!empty($get->get('connId'))) {

            // Fetch connection data
            $connection = $connections->fetchById($get->get('connId'));
            $view->set('connection', $connection);
        }

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->requireOne([ 'firstName', 'lastName' ]);

        // Insert new contact
        $contact = $contacts->insert(new \Frootbox\Ext\Core\HelpAndSupport\Persistence\Contact([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPage()->getId(),
            'firstName' => $post->get('firstName'),
            'lastName' => $post->get('lastName'),
            'visibility' => (DEVMODE ? 2 : 1),
        ]));

        // Add contact to category
        if ($get->get('categoryId')) {

            // Add contact to category
            $category = $categoriesRepository->fetchById($get->get('categoryId'));
            $category->connectItem($contact);

            return self::getResponse('json', 200, [
                'modalDismiss' => true,
                'replace' => [
                    'selector' => '#contactsCategoryListReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Categories\Partials\ContactsList\Partial::class, [
                        'highlight' => $contact->getId(),
                        'plugin' => $this->plugin,
                        'category' => $category
                    ])
                ]
            ]);
        }
        else {

            return self::getResponse('json', 200, [
                'modalDismiss' => true,
                'replace' => [
                    'selector' => '#contactsListReceiver',
                    'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Contact\Partials\ContactsList\Partial::class, [
                        'highlight' => $contact->getId(),
                        'plugin' => $this->plugin
                    ])
                ]
            ]);
        }
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch contact
        $contact = $contacts->fetchById($get->get('contactId'));

        $contact->delete();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#contactsListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Contact\Partials\ContactsList\Partial::class, [
                    'highlight' => $contact->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxSortAction(
        Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository,
    ): Response
    {
        $rows = $get->get('row');
        $orderId = count($rows) + 1;

        foreach ($rows as $contactId) {

            $contact = $contactsRepository->fetchById($contactId);
            $contact->setOrderId($orderId--);
            $contact->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxSwitchVisibleAction(
        Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository
    ): Response
    {
        // Fetch contact
        $contact = $contactsRepository->fetchById($get->get('contactId'));
        $contact->visibilityPush();

        return self::getResponse('json', 200, [
            'removeClass' => [
                'selector' => '.visibility[data-contact="' . $contact->getId() . '"]',
                'className' => 'visibility-0 visibility-1 visibility-2'
            ],
            'addClass' => [
                'selector' => '.visibility[data-contact="' . $contact->getId() . '"]',
                'className' => $contact->getVisibilityString()
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Persistence\Repositories\CategoriesConnections $connections,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch contact
        $contact = $contacts->fetchById($get->get('contactId'));

        $contact->setData($post->get('person'));
        $contact->save();

        // Update connection
        if (!empty($get->get('connId'))) {

            // Fetch connection
            $connection = $connections->fetchById($get->get('connId'));

            $connection->addConfig($post->get('connConfig'));
            $connection->save();
        }

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#contactsListReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Contact\Partials\ContactsList\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
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