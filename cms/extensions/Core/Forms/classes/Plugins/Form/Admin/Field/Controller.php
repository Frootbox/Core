<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Field;

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
    public function ajaxModalComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch group
        $group = $groups->fetchById($get->get('groupId'));

        $view->set('group', $group);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxModalEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch field
        $field = $fields->fetchById($get->get('fieldId'));

        $view->set('field', $field);

        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([
            'field.title'
        ]);

        // Prepare data
        $data = $post->get('field');

        // Parse title
        if (mb_substr($data['title'], -1) == '*') {
            $data['title'] = trim(mb_substr($data['title'], 0, -1));
            $data['config']['isRequired'] = true;
        }

        // Fetch group
        $group = $groups->fetchById(($get->get('groupId')));

        // Create field
        $field = $fields->insert(new \Frootbox\Ext\Core\Forms\Persistence\Field(array_replace_recursive([
            'pluginId' => $this->plugin->getId(),
            'pageId' => $this->plugin->getPageId(),
            'parentId' => $group->getId()
        ], $data)));


        if (preg_match('#straße#i', $field->getTitle())) {
            $field->addConfig([
                'type' => 'StreetAndNumber'
            ]);
            $field->save();
        }

        if (preg_match('#ort|stadt#i', $field->getTitle())) {
            $field->addConfig([
                'type' => 'ZipcodeAndCity'
            ]);
            $field->save();
        }

        if (preg_match('#e\-mail#i', $field->getTitle())) {
            $field->addConfig([
                'type' => 'Email'
            ]);
            $field->save();
        }

        if (preg_match('#nachricht|anmerkung|bemerkung#i', $field->getTitle())) {
            $field->addConfig([
                'type' => 'Textarea'
            ]);
            $field->save();
        }

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReciever_' . $group->getId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group\Partials\ListFields::class, [
                    'group' => $group,
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
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch field
        $field = $fields->fetchById($get->get('fieldId'));

        // Delete field
        $field->delete();

        // Fetch group
        $group = $groups->fetchById($field->getParentId());

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReciever_' . $group->getId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group\Partials\ListFields::class, [
                    'group' => $group,
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
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields
    ): \Frootbox\Admin\Controller\Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $rowId) {

            $row = $fields->fetchById($rowId);

            $row->setOrderId($orderId--);
            $row->save();
        }

        return self::getResponse('json', 200, [ ]);
    }

    /**
     *
     */
    public function ajaxSwitchRequiredAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\Admin\Controller\Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        $field->addConfig([
            'isRequired' => !$field->getConfig('isRequired')
        ]);
        $field->save();

        return self::getResponse('json', 200, [
            'setIconPrefix' => [
                'selector' => '.switch-required[data-field="' . $field->getId() . '"] .icon',
                'prefix' => ($field->getConfig('isRequired') ? 'fas' : 'fal')
            ],
            'success' => 'Die Daten wurden gespeichert.'
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): \Frootbox\Admin\Controller\Response
    {
        // Validate required input
        $post->require([ 'field.title', 'field.config.type' ]);

        // Fetch field
        $field = $fields->fetchById($get->get('fieldId'));

        // Update field
        $field->setData($post->get('field'));
        $field->save();

        // Fetch group
        $group = $groups->fetchById($field->getParentId());

        return self::getResponse('json', 200, [
            'replace' => [
                'selector' => '#fieldsReciever_' . $group->getId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Group\Partials\ListFields::class, [
                    'group' => $group,
                    'highlight' => $field->getId(),
                    'plugin' => $this->plugin
                ])
            ],
            'modalDismiss' => true
        ]);
    }
}
