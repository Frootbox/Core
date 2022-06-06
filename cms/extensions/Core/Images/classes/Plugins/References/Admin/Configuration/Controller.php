<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Configuration;

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
    public function getForms(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $formsRepository->fetch([

        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxTriggerSaveAction(
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository,
    ): Response
    {
        // Fetch references
        $result = $referencesRepository->fetch();

        foreach ($result as $reference) {
            $reference->save();
        }

        return self::getResponse('json', 200, [
            'success' => 'Alle Referenzen wurden neu gespeichert.',
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References $referencesRepository
    ): Response
    {
        // Update config
        $this->plugin->addConfig([
            'order' => $post->get('order'),
            'noReferencesDetailPage' => $post->get('noReferencesDetailPage'),
            'formId' => $post->get('formId'),
        ]);

        $this->plugin->save();

        // Update entities
        $result = $referencesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $entity) {

            $entity->addConfig([
                'noReferencesDetailPage' => $post->get('noReferencesDetailPage'),
            ]);

            $entity->save();
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
