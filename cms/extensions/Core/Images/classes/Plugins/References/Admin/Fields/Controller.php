<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Admin\Fields;

use Frootbox\Admin\Controller\Response;

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
    public function ajaxModalComposeAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        return self::getResponse('plain', 200, [
            'field' => $field,
        ]);
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate input
        $post->require([ 'title' ]);

        // Insert new field
        $field = $fieldsRepository->insert(new \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Field([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title'),
        ]));

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Fields\Partials\ListFields::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $field->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));
        $field->delete();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Fields\Partials\ListFields::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $field->getId()
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
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $fieldId) {

            $field = $fieldsRepository->fetchById($fieldId);
            $field->setOrderId($orderId--);
            $field->save();
        }

        return self::getResponse('json', 200);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        $field->setTitle($post->get('title'));
        $field->save();

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Images\Plugins\References\Admin\Fields\Partials\ListFields::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $field->getId()
                ])
            ],
            'modalDismiss' => true
        ]);
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
