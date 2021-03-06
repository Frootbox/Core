<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\Uid;

    protected $publicActions = [
        'index',
        'showContact',
        'showCategory',
    ];

    protected $isContainerPlugin = true;
    protected $icon = 'fas fa-users';

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
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $cloningMachine = $container->get(\Frootbox\CloningMachine::class);

        // Fetch contacts
        $contactRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts::class);

        // Fetch contacts
        $contacts = $contactRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ]
        ]);

        foreach ($contacts as $contact) {

            $newContact = $contact->duplicate();

            $newContact->setPluginId($this->getId());
            $newContact->setPageId($this->getPage()->getId());
            $newContact->setAlias(null);

            $newContact->save();

            // Clone contents
            $cloningMachine->cloneContentsForElement($newContact, $contact->getUidBase());
        }
    }

    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories
    ): ?\Frootbox\Db\Row
    {
        // Fetch top categories
        $category = $categories->fetchOne([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId'),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
        ]);

        return $category;
    }

    /**
     *
     */
    public function getContacts(): \Frootbox\Db\Result
    {
        $contactRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts::class);

        // Fetch contacts
        $contacts = $contactRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ]
        ]);

        return $contacts;
    }

    /**
     *
     */
    public function getTopCategories(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories
    ): \Frootbox\Db\Result
    {
        // Fetch top categories
        $result = $categories->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('parentId', 'rootId'),
            ],
            'order' => [ 'lft ASC' ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contacts,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories
    ): void
    {
        // Clean contact persons
        $result = $contacts->fetch([
            'where' => [ 'pluginId' => $this->getId() ]
        ]);

        $result->map('delete');

        // Fetch categories
        $result = $categories->fetch([
            'where' => [
                'uid' => $this->getUid('categories')
            ],
            'order' => [ 'lft DESC' ]
        ]);

        $result->map('delete');
    }

    /**
     *
     */
    public function showCategoryAction(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categories,
        \Frootbox\View\Engines\Interfaces\Engine $view
    )
    {
        // Fetch category
        $category = $categories->fetchById($this->getAttribute('categoryId'));
        $view->set('category', $category);
    }

    /**
     *
     */
    public function showContactAction(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository
    ): Response
    {
        // Fetch contact
        $contact = $contactsRepository->fetchById($this->getAttribute('contactId'));

        return new Response([
            'contact' => $contact
        ]);
    }
}
