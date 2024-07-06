<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\Apps\FormsManager;

use Frootbox\Admin\Controller\Response;
use Frootbox\Admin\View;
use Frootbox\Http\Get;

class App extends \Frootbox\Admin\Persistence\AbstractApp
{
    /**
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    protected function getAvailableFields(): array
    {
        $extensionController = new \Frootbox\Ext\Core\ContactForms\ExtensionController;

        $directory = new \Frootbox\Filesystem\Directory($extensionController->getPath() . 'classes/Persistence/Fields/');

        $list = [];
        $loop = 0;

        foreach ($directory as $file) {

            if ($file == 'AbstractField.php') {
                continue;
            }

            $languageFile = $directory->getPath() . $file . '/resources/private/language/de-DE.php';

            $data = require($languageFile);

            $title = $data['Field.Title'] ?? $file;

            $list[$title . ++$loop] = [
                'title' => $title,
                'fieldType' => $file,
            ];
        }

        ksort($list);

        return $list;
    }

    /**
     *
     */
    public function ajaxArchiveLogDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository,
    ): Response
    {
        // Fetch log
        $log = $logsRepository->fetchById($get->get('logId'));
        $log->delete();

        return new Response('json', 200, [
            'fadeOut' => '[data-log="' . $log->getId() . '"]',
        ]);
    }

    /**
     *
     */
    public function ajaxArchiveLogSendAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): Response
    {
        // Fetch log
        $log = $logsRepository->fetchById($get->get('logId'));

        // Fetch form
        $form = $formsRepository->fetchById($log->getParentId());



        // Merge dedicated recipients with channel recipients
        $recipients = [];
        if (!empty($form->getConfig('recipients'))) {
            $recipients = array_merge($recipients, explode(',', $form->getConfig('recipients')));
        }

        $logData = $log->getLogData();
        $logData['recipients'] = $recipients;

        $extensionController = new \Frootbox\Ext\Core\ContactForms\ExtensionController;

        $view->set('formlog', $log);
        $view->set('form', $form);

        $file = $extensionController->getPath() . 'resources/private/views/builder/Mail.html.twig';

        $source = $view->render($file);

        if (!empty($form->getConfig('modSubject'))) {
            $subject = $form->getConfig('modSubject');
        }
        elseif (!empty($form->getTitle())) {

            $subject = 'Anfrage: ' . $form->getTitle();

            /*
            if ($page->getTitle() != $this->getTitle()) {
                $subject .= '/' . $page->getTitle();
            }
            */
        }
        else {
            $subject = 'Kontaktanfrage über Ihre Website';
        }

        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject($subject);
        $mail->setBodyHtml($source);

        // Add attachments
        $attachments = [];

        foreach ($logData['formData'] as $section) {
            foreach ($section['fields'] as $field) {

                if ($field['type'] != 'Files') {
                    continue;
                }

                if (empty($field['value'])) {
                    continue;
                }

                foreach ($field['value'] as $xfile) {
                    $attachments[] = $files->fetchById($xfile['id']);
                }
            }
        }

        foreach ($attachments as $file) {
            $attachment = new \Frootbox\Mail\Attachment(FILES_DIR . $file->getPath(), $file->getName());
            $mail->addAttachment($attachment);
        }

        foreach ($recipients as $recipient) {

            $mail->clearTo();
            $mail->addTo($recipient);

            $mailTransport->send($mail);
        }

        return new Response('json', 200, [
            'success' => 'Anfrage wurde erneut an den Verteiler gesendet.',
        ]);
    }

    /**
     *
     */
    public function ajaxConfigUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));
        $form->setTitle($post->get('title'));
        $form->setParentId($post->get('categoryId'));
        $form->save();

        $form->addConfig([
            'recipients' => $post->get('recipients'),
            'modSubject' => $post->get('modSubject'),
            'customSubject' => $post->get('customSubject'),
            'replyTo' => $post->get('replyTo'),
            'textAboveMail' => $post->get('textAboveMail'),
            'textBelowMail' => $post->get('textBelowMail'),
            'feedback' => $post->get('feedback'),
            'callback' => $post->get('callback'),
            'autoReplyDeaktivated' => !empty($post->get('autoReplyDeaktivated')),
            'feedbackPageId' => $post->get('feedbackPageId'),
        ]);

        $form->save();

        return new Response('json');
    }

    /**
     * @param Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Categories $categoryRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Categories $categoryRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        $post->require([ 'title' ]);

        // Fetch category
        $category = $categoryRepository->fetchById($get->get('categoryId'));

        // Insert new form
        $form = $formsRepository->insert(new \Frootbox\Ext\Core\ContactForms\Persistence\Form([
            'title' => $post->get('title'),
            'parentId' => $category->getId(),
        ]));

        // Insert new group
        $group = $groupsRepository->insert(new \Frootbox\Ext\Core\ContactForms\Persistence\Group([
            'parentId' => $form->getId(),
            'title' => null,
            'config' => [
                'columns' => '12',
            ],
        ]));

        $fields = [
            [
                'title' => 'Name, Vorname',
                'type' => 'Text',
                'mandatory' => true,
            ],
            [
                'title' => 'E-Mail',
                'type' => 'Email',
                'mandatory' => true,
            ],
            [
                'title' => 'Nachricht',
                'type' => 'Textarea',
                'mandatory' => true,
            ],
            [
                'title' => 'Datenschutz',
                'type' => 'Privacy',
                'mandatory' => true,
            ],
            [
                'title' => 'Senden',
                'type' => 'Button',
                'mandatory' => false,
            ],
        ];

        // Compose new field
        foreach ($fields as $field) {

            $fieldClass = '\\Frootbox\\Ext\\Core\\ContactForms\\Persistence\\Fields\\' . $field['type'] . '\\Field';
            $field = new $fieldClass([
                'title' => $field['title'],
                'parentId' => $group->getId(),
                'className' => \Frootbox\Ext\Core\ContactForms\Persistence\Field::class,
                'customClass' => $fieldClass,
                'config' => [
                    'type' => $field['type'],
                    'column' => 1,
                    'mandatory' => $field['mandatory'],
                ],
            ]);

            // Insert new field
            $fieldsRepository->insert($field);
        }

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#formsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListForms::class, [
                    'app' => $this,
                    'highlight' => $form->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    public function ajaxDuplicateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        // Compose new form
        $newForm = new \Frootbox\Ext\Core\ContactForms\Persistence\Form([
            'title' => $form->getTitle() . ' (Kopie)',
            'config' => $form->getConfig(),
        ]);

        $newForm = $formsRepository->insert($newForm);

        foreach ($form->getGroups() as $group) {

            $newGroup = new \Frootbox\Ext\Core\ContactForms\Persistence\Group([
                'parentId' => $newForm->getId(),
                'title' => $group->getTitle(),
                'config' => [
                    'columns' => $group->getConfig('columns'),
                ],
            ]);

            $newGroup = $groupsRepository->insert($newGroup);

            foreach ($group->getFields() as $field) {

                $fieldClass = $field->getCustomClass();

                // Copy field
                $newField = new $fieldClass([
                    'title' => $field->getTitle(),
                    'parentId' => $newGroup->getId(),
                    'className' => \Frootbox\Ext\Core\ContactForms\Persistence\Field::class,
                    'customClass' => $fieldClass,
                    'config' => [
                        'type' => $field->getConfig('type'),
                        'column' => $field->getConfig('column'),
                        'mandatory' => $field->getConfig('mandatory'),
                    ],
                ]);

                $fieldsRepository->insert($newField);
            }
        }

        return new Response('json', 200, [

        ]);
    }

    public function ajaxExportAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository,
    ): Response
    {
        /**
         * Fetch form
         */
        $form = $formRepository->fetchById($get->get('formId'));

        // Fetch logs
        $result = $logsRepository->fetch([
            'where' => [
                'parentId' => $form->getId(),
            ],
            'order' => [
                'date DESC',
            ]
        ]);

        $loop = 0;

        $f = fopen('php://memory', 'w');

        foreach ($result as $row) {

            $logData = $row->getLogData();

            if ($loop == 0) {

                $row = [];

                foreach ($logData['formData'] as $group) {
                    foreach ($group['fields'] as $field) {
                        $row[] = $field['title'];
                    }
                }

                fputcsv($f, $row, ';');
            }

            $row = [];


            foreach ($logData['formData'] as $group) {
                foreach ($group['fields'] as $field) {
                    $row[] = $field['valueDisplay'];
                }
            }

            fputcsv($f, $row, ';');

            ++$loop;
        }

        fseek($f, 0);

        header('Content-Type: text/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="export.csv";');
        // make php send the generated csv lines to the browser
        fpassthru($f);

        exit;
    }

    /**
     *
     */
    public function ajaxFieldCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch group
        $group = $fieldsRepository->fetchById($get->get('groupId'));

        $fieldClass = '\\Frootbox\\Ext\\Core\\ContactForms\\Persistence\\Fields\\' . $post->get('type') . '\\Field';

        // Compose new field
        $field = new $fieldClass([
            'title' => ((substr($post->get('title'), -1) == '*') ? substr($post->get('title'), 0, -1) : $post->get('title')),
            'parentId' => $group->getId(),
            'className' => \Frootbox\Ext\Core\ContactForms\Persistence\Field::class,
            'customClass' => $fieldClass,
            'config' => [
                'type' => $post->get('type'),
                'column' => $get->get('column'),
                'mandatory' => (substr($post->get('title'), -1) == '*'),
            ],
        ]);

        // Insert new field
        $field = $fieldsRepository->insert($field);

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'form' => $group->getForm(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxFieldDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        $field->delete();

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'form' => $field->getGroup()->getForm(),
                ]),
            ],
            'success' => 'Das Formular-Feld wurde gelöscht.',
            'modalDismiss' => true,
        ]);

    }

    /**
     *
     */
    public function ajaxFieldSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $fieldId) {
            $field = $fieldsRepository->fetchById($fieldId);
            $field->setOrderId($orderId--);
            $field->save();
        }

        return new Response(type: 'json', body: [
            'success' => 'Die neue Reihenfolge wurde gespeichert.',
        ]);
    }

    /**
     *
     */
    public function ajaxFieldUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        $fieldClass = '\\Frootbox\\Ext\\Core\\ContactForms\\Persistence\\Fields\\' . $post->get('type') . '\\Field';

        $title = $post->get('titles') ? $post->get('titles')[GLOBAL_LANGUAGE] : $post->get('title');

        // Update field
        $field->setTitle($title);
        $field->setCustomClass($fieldClass);
        $field->addConfig([
            'titles' => $post->get('titles'),
            'type' => $post->get('type'),
            'mandatory' => $post->get('mandatory'),
            'helpText' => $post->get('helpText'),
            'placeholder' => $post->get('placeholder'),
            'presetValue' => $post->get('presetValue'),
            'presetValueFromGet' => !empty($post->get('presetValueFromGet')),
        ]);

        $field->updateFromPost($post);

        $field->save();

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'form' => $field->getGroup()->getForm(),
                ]),
            ],
            'success' => 'Die Daten wurden gespeichert.',
            'modalDismiss' => true,
        ]);

    }

    /**
     *
     */
    public function ajaxFormDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        $form->delete();

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#formsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListForms::class, [
                    'highlight' => $form->getId(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxGroupCreateAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    )
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        // Insert new group
        $group = $groupsRepository->insert(new \Frootbox\Ext\Core\ContactForms\Persistence\Group([
            'parentId' => $form->getId(),
            'title' => $post->get('title'),
            'config' => [
                'columns' => $post->get('columns'),
            ],
        ]));

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'highlight' => $group->getId(),
                    'form' => $form,
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxGroupDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch group
        $group = $groupsRepository->fetchById($get->get('groupId'));

        $group->delete();

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'form' => $group->getForm(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxGroupSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
    ): Response
    {
        $loopId = count($get->get('groups')) + 1;

        foreach ($get->get('groups') as $groupId) {

            // Fetch group
            $group = $groupsRepository->fetchById($groupId);

            $group->setOrderId($loopId--);
            $group->save();
        }

        return new Response('json');
    }

    /**
     *
     */
    public function ajaxGroupUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch group
        $group = $groupsRepository->fetchById($get->get('groupId'));

        $group->setTitle($post->get('title'));
        $group->addConfig([
            'columns' => $post->get('columns'),
            'alignItemsEnd' => $post->get('alignItemsEnd'),
            'oneFieldIsRequired' => $post->get('oneFieldIsRequired'),
            'className' => $post->get('className'),
        ]);

        $group->save();

        return new Response('json', 200, [
            'replace' => [
                'selector' => '#groupsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ContactForms\Apps\FormsManager\Partials\ListGroups::class, [
                    'highlight' => $group->getId(),
                    'form' => $group->getForm(),
                ]),
            ],
            'modalDismiss' => true
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): Response
    {
        return new Response;
    }

    /**
     *
     */
    public function ajaxModalFieldComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository
    ): Response
    {
        // Fetch group
        $group = $groupsRepository->fetchById($get->get('groupId'));

        return new Response('html', 200, [
            'group' => $group,
            'fields' => $this->getAvailableFields(),
        ]);
    }

    /**
     *
     */
    public function ajaxModalFieldEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        $group = $field->getGroup();
        $form = $group->getForm();

        // Get options html
        $viewFile = $field->getPath() . 'resources/private/views/Admin.html.twig';

        if (file_exists($viewFile)) {
            $html = $view->render($viewFile, null, [
                'field' => $field,
            ]);
        }

        return new Response('html', 200, [
            'field' => $field,
            'groups' => $form->getGroups(),
            'availableFields' => $this->getAvailableFields(),
            'optionsHtml' => $html ?? (string) null,
        ]);
    }

    /**
     *
     */
    public function ajaxModalGroupComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        return new Response('html', 200, [
            'form' => $form,
        ]);
    }

    /**
     *
     */
    public function ajaxModalGroupEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Groups $groupsRepository,
    ): Response
    {
        // Fetch group
        $group = $groupsRepository->fetchById($get->get('groupId'));

        return new Response('html', 200, [
            'group' => $group,
        ]);
    }

    public function ajaxPanelFormsAction(

    ): Response
    {
        return new Response('html', 200, [

        ]);
    }

    /**
     *
     */
    public function archiveAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        // Fetch logs
        $result = $logsRepository->fetch([
            'where' => [
                'parentId' => $form->getId(),
            ],
            'order' => [
                'date DESC',
            ]
        ]);

        return new Response('html', 200, [
            'form' => $form,
            'logs' => $result,
        ]);
    }

    /**
     *
     */
    public function archiveLogAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logsRepository
    ): Response
    {
        // Fetch log
        $log = $logsRepository->fetchById($get->get('logId'));

        return new Response('html', 200, [
            'form' => $log->getForm(),
            'log' => $log,
        ]);
    }

    /**
     *
     */
    public function configAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Categories $categoryRepository,
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        // Fetch parent category
        $parent = $categoryRepository->fetchOne([
            'where' => [
                'uid' => $this->getUid('categories'),
                'parentId' => 0,
            ],
        ]);

        // Fetch category tree
        $categories = $categoryRepository->getTree($parent->getId());

        // Fetch pages
        $pages = $pageRepository->fetch([
            'order' => [ 'lft ASC' ],
        ]);

        return new Response('html', 200, [
            'form' => $form,
            'Pages' => $pages,
            'categories' => $categories,
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): Response
    {
        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        return new Response('html', 200, [
            'form' => $form,
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse('html');
    }
}
