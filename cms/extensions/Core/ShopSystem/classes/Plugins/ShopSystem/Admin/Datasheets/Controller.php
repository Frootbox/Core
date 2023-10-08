<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Interfaces\ResponseInterface;

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
    public function ajaxModalFieldComposeAction(): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalFieldDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFields,
        \Frootbox\Admin\View $view
    ): Response
    {
        // Fetch fields
        $field = $datasheetsFields->fetchById($get->get('fieldId'));
        $view->set('field', $field);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalGroupComposeAction(

    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalGroupDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    ): Response
    {
        // Fetch group
        $group = $datasheetOptionGroupRepository->fetchById($get->get('groupId'));

        return self::getResponse('plain', 200, [
            'group' => $group,
        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Insert new datasheet
        $datasheet = $datasheets->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Datasheet([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title')
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\DatasheetsList\Partial::class, [
                    'highlight' => $datasheet->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     */
    public function ajaxDeleteAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch datasheet
        $datasheet = $datasheets->fetchById($get->get('datasheetId'));


        $check = $productsRepository->fetchOne([
            'where' => [
                'datasheetId' => $datasheet->getId(),
            ],
        ]);

        if ($check !== null) {
            throw new \exception('Diesem Datenblatt sind Produkte zugeordnet, es kann nicht gelöscht werden.');
        }

        $datasheet->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\DatasheetsList\Partial::class, [
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFields
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     */
    public function ajaxFieldDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFields,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch datasheet field
        $field = $datasheetsFields->fetchById($get->get('fieldId'));

        // Fetch datasheet
        $datasheet = $datasheets->fetchById($field->getParentId());

        // Delete field
        $field->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetFieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\FieldsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'datasheet' => $datasheet,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxFieldCreateAction (
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFields,
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch datasheet
        $datasheet = $datasheets->fetchById($get->get('datasheetId'));

        // Insert new datasheetfields
        $field = $datasheetsFields->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetField([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'parentId' => $datasheet->getId(),
            'title' => $post->get('title'),
            'config' => [
                'section' => $post->get('section')
            ]
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetFieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\FieldsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $field->getId(),
                    'datasheet' => $datasheet
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxFieldUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFields,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData $productsDataRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch fields
        $field = $datasheetsFields->fetchById($get->get('fieldId'));

        $field->setTitle($post->get('title'));
        $field->setType($post->get('type'));
        $field->setSection($post->get('section'));

        $field->save();

        // Update data fields
        $dataFields = $productsDataRepository->fetch([
            'where' => [
                'fieldId' => $field->getId(),
            ],
        ]);

        foreach ($dataFields as $dataField) {

            $dataField->setType($post->get('type'));
            $dataField->updateMetrics();
            $dataField->save();
        }

        // Log action
        $this->log('ShopDatasheetFieldUpdate', [
            $field->getId(),
            $field->getTitle(),
            get_class($field),
        ]);

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetFieldsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\FieldsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $field->getId(),
                    'datasheet' => $field->getDatasheet(),
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxFieldsSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields $datasheetsFieldsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach($get->get('row') as $fieldId) {

            // Fetch field
            $field = $datasheetsFieldsRepository->fetchById($fieldId);

            $field->setOrderId(--$orderId);
            $field->save();
        }

        return self::getResponse('json', 200, [

        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository
     * @return Response
     */
    public function ajaxGroupCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    ): Response
    {
        // Fetch datasheet
        $datasheet = $datasheetRepository->fetchById($get->get('datasheetId'));

        // Insert new datasheet group
        $group = $datasheetOptionGroupRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetOptionGroup([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'parentId' => $datasheet->getId(),
            'title' => $post->get('title'),
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetGroupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\GroupsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $group->getId(),
                    'datasheet' => $datasheet,
                ])
            ]
        ]);
    }

    public function ajaxGroupDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    ): Response
    {
        // Fetch group
        $group = $datasheetOptionGroupRepository->fetchById($get->get('groupId'));

        // Obtain datasheet
        $datasheet = $group->getDatasheet();

        $group->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetGroupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\GroupsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $group->getId(),
                    'datasheet' => $datasheet,
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxGroupUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    ): Response
    {
        // Fetch group
        $group = $datasheetOptionGroupRepository->fetchById($get->get('groupId'));

        $group->setTitle($post->get('title'));
        $group->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#datasheetGroupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Datasheets\Partials\GroupsList\Partial::class, [
                    'plugin' => $this->plugin,
                    'highlight' => $group->getId(),
                    'datasheet' => $group->getDatasheet(),
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetRepository,
    ): Response
    {
        // Fetch datasheet
        $datasheet = $datasheetRepository->fetchById($get->get('datasheetId'));

        $datasheet->setTitle($post->get('title'));
        $datasheet->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdatePresetsAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets
    ): Response
    {
        // Fetch datasheet
        $datasheet = $datasheets->fetchById($get->get('datasheetId'));

        $datasheet->addConfig([
            'products' => [
                'templateId' => $post->get('templateId')
            ]
        ]);
        $datasheet->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheets,
        \Frootbox\Config\Config $config
    ): Response
    {
        // Fetch datasheet
        $datasheet = $datasheets->fetchById($get->get('datasheetId'));

        $view->set('datasheet', $datasheet);


        // Fetch available product details templates
        $templates = $this->plugin->getLayoutsForAction($config, 'ShowProduct');
        $view->set('productDetailsTemplates', $templates);


        return self::getResponse();
    }


    /**
     *
     */
    public function indexAction ( ): Response
    {
        return self::getResponse();
    }
}