<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Config;

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
    public function ajaxImportAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository
    ): Response
    {
        // Obtain export data
        $data = json_decode($post->get('import'), true);

        foreach ($data['groups'] as $groupData) {

            $group = new \Frootbox\Ext\Core\Forms\Persistence\Group($groupData['data']);
            $group->setPageId($this->plugin->getPageId());
            $group->setPluginId($this->plugin->getId());

            $group = $groupsRepository->insert($group);

            foreach ($groupData['fields'] as $fieldData) {

                $field = new \Frootbox\Ext\Core\Forms\Persistence\Field($fieldData);
                $field->setPageId($this->plugin->getPageId());
                $field->setPluginId($this->plugin->getId());
                $field->setParentId($group->getId());

                $field = $fieldsRepository->insert($field);
            }
        }

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Set new config
        $this->plugin->addConfig($post->get('config'));
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateTextsAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Set new config
        $this->plugin->addConfig($post->get('config'));
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\View $view
    ): Response
    {
        // Fetch form groups
        $result = $groupsRepository->fetch([
            'where' => [ 'pluginId' => $this->plugin->getId() ]
        ]);

        $export = [
            'groups' => [ ]
        ];

        foreach ($result as $group) {

            $groupData = [
                'data' => $group->getData(),
                'fields' => [ ]
            ];

            foreach ($group->getFields($fieldsRepository) as $field) {
                $groupData['fields'][] = $field->getData();
            }

            $export['groups'][] = $groupData;
        }

        if (!empty($export['groups'])) {
            $view->set('exportData', json_encode($export));
        }

        // Fetch captchas
        $path = $this->getExtensionController()->getPath() . 'classes/Captchas/';
        $directory = new \Frootbox\Filesystem\Directory($path);

        $captchas = [];

        foreach ($directory as $file) {

            if ($file == 'CaptchaInterface.php') {
                continue;
            }

            $captchas[] = [
                'captcha' => $file,
                'title' => $file,
                'captchaClass' => '\\Frootbox\\Ext\\Core\\Forms\\Captchas\\' . $file
            ];
        }

        $view->set('captchas', $captchas);

        return self::getResponse();
    }
}
