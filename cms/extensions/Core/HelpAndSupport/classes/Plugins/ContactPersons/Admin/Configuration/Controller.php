<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Admin\Configuration;

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
     *
     */
    public function ajaxImportConnectedPersonsAction(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categoriesRepository,
    ): Response
    {
        // Fetch categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        $loop = 0;

        foreach ($result as $category) {

            foreach ($category->getContacts() as $contact) {

                if ($contact->getPluginId() != $this->plugin->getId()) {

                    $contact->unsetConfig('noPersonsDetailPage');

                    $contact->addConfig([
                        'noPersonsDetailPage' => $this->plugin->getConfig('noPersonsDetailPage'),
                        'imported' => $_SERVER['REQUEST_TIME'],
                    ]);

                    $contact->setPluginId($this->plugin->getId());
                    $contact->save();

                    ++$loop;
                }
            }
        }

        return self::getResponse('json', 200, [
            'success' => $loop . ' Kontakte wurden importiert.',
        ]);
    }

    /**
     *
     */
    public function ajaxImportPersonsAction(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository,
    ): Response
    {
        // Fetch persons
        $persons = $contactRepository->fetch([
            'where' => [

            ],
        ]);

        $loop = 0;

        foreach ($persons as $person) {

            $person->setPageId($this->plugin->getPageId());
            $person->setPluginId($this->plugin->getId());
            $person->save();

            ++$loop;
        }

        return self::getResponse('json', 200, [
            'success' => $loop . ' Kontakte wurden importiert.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactsRepository,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'noPersonsDetailPage' => $post->get('noPersonsDetailPage'),
            'noCategoriesDetailPage' => $post->get('noCategoriesDetailPage'),
            'defaultSorting' => $post->get('defaultSorting'),
        ]);

        $this->plugin->save();

        // Update persons
        $result = $contactsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $person) {
            $person->addConfig([
                'noPersonsDetailPage' => $post->get('noPersonsDetailPage'),
            ]);

            $person->save();
        }

        // Update categories
        $result = $categoriesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $category) {
            $category->addConfig([
                'noCategoriesDetailPage' => $post->get('noCategoriesDetailPage'),
            ]);
            $category->save();
        }

        return self::getResponse('json', 200, [

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
