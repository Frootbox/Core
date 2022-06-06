<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Viewhelper;

class Contacts extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getCategory' => [
            'categoryId',
        ],
        'getContact' => [
            'contactId',
        ],
    ];

    /**
     *
     */
    public function getCategoryAction(
        int $categoryId,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Ext\Core\HelpAndSupport\Persistence\Category
    {
        return $categoriesRepository->fetchById($categoryId);
    }

    /**
     *
     */
    public function getContactAction(
        $contactId,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository
    )
    {
        // Fetch contact
        $contact = $contactsRepository->fetchById($contactId);

        return $contact;
    }

    /**
     *
     */
    public function getContactsAction(
        array $params = null,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch contacts
        $contacts = $contactsRepository->fetch();

        return $contacts;
    }
}
