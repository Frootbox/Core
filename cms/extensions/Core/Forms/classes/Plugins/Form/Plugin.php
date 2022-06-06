<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\StandardUrls;

    protected $publicActions = [
        'index',
        'complete'
    ];

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
        // Clone groups
        $groups = $container->call([ $ancestor, 'getGroups' ]);

        foreach ($groups as $group) {

            $newGroup = $group->duplicate();
            $newGroup->setPageId($this->getPageId());
            $newGroup->setPluginId($this->getId());
            $newGroup->save();

            // Clone fields
            $fields = $container->call([ $group, 'getFields' ]);

            foreach ($fields as $field) {

                $newField = $field->duplicate();
                $newField->setParentId($newGroup->getId());
                $newField->setPageId($newGroup->getPageId());
                $newField->setPluginId($newGroup->getPluginId());
                $newField->save();
            }
        }
    }

    /**
     *
     */
    public function onAfterCreate(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fieldsRepository
    ): void
    {
        // Insert first group
        $group = $groupsRepository->insert(new \Frootbox\Ext\Core\Forms\Persistence\Group([
            'pageId' => $this->getPageId(),
            'pluginId' => $this->getId(),
            'title' => 'Anfrage',
        ]));

        $fields = [
            [
                'title' => 'Name, Vorname',
            ],
            [
                'title' => 'E-Mail',
                'config' => [
                    'type' => 'Email',
                    'isRequired' => true,
                ],
            ],
            [
                'title' => 'Nachricht',
                'config' => [
                    'type' => 'Textarea',
                    'isRequired' => true,
                ],
            ]
        ];

        // Create fields
        foreach ($fields as $fieldData) {

            $field = $fieldsRepository->insert(new \Frootbox\Ext\Core\Forms\Persistence\Field(array_replace_recursive([
                'pluginId' => $this->getId(),
                'pageId' => $this->getPageId(),
                'parentId' => $group->getId()
            ], $fieldData)));
        }

    }

    /**
     *
     */
    public function onBeforeDelete(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groupsRepository
    ): void
    {
        // Fetch groups
        $groups = $this->getGroups($groupsRepository);
        $groups->map('delete');
    }

    /**
     *
     */
    public function getAlternatePrivacy(string $privacyUrl = null): ?string
    {
        $text = $this->getConfig('texts.privacy');

        if (!empty($privacyUrl)) {
            $text = preg_replace('#\[(.*?)\]#', '<a href="' . $privacyUrl . '">\\1</a>', $text);
        }

        return $text;
    }

    /**
     *
     */
    public function getForm(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields
    )
    {
        $form = new \Frootbox\Ext\Core\Forms\Persistence\Form;

        $result = $groups->fetch([
            'where' => [
                'pluginId' => $this->getId()
            ]
        ]);

        foreach ($result as $group) {

            $form->addGroup($group);
        }

        d($form);
    }

    /**
     *
     */
    public function getGroups(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups
    ): \Frootbox\Db\Result
    {
        // Fetch form groups
        $result = $groups->fetch([
            'where' => [ 'pluginId' => $this->getId() ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxGetFilesBucketAction(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields,
        \Frootbox\Http\Get $get
    ): Response
    {
        // Fetch field
        $field = $fields->fetchById($get->get('fieldId'));


        $list = [];

        if (!empty($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')])) {

            foreach ($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')] as $tempId) {

                $list[] = new \Frootbox\Ext\Core\FileManager\TempFile($tempId);
            }
        }

        return new \Frootbox\View\ResponseView([
            'field' => $field,
            'files' => $list,
        ]);
    }

    /**
     *
     */
    public function ajaxRemoveTempFileAction(
        \Frootbox\Http\Get $get
    ): Response
    {
        if (!empty($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')])) {

            foreach ($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')] as $index => $fileId) {
                if ($fileId == $get->get('fileId')) {
                    unset($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')][$index]);
                }
            }
        }

        $file = new \Frootbox\Ext\Core\FileManager\TempFile($get->get('fileId'));
        $file->delete();

        return new \Frootbox\View\ResponseJson([

        ]);
    }

    /**
     *
     */
    public function ajaxSubmitAction(
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Logs $logs,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Fields $fields,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Groups $groups,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport
    ): Response
    {
        // Check privacy policy consent
        if (empty($post->get('privacyPolicy'))) {
            throw new \Frootbox\Exceptions\InputMissing('AcceptPrivacyPolicy');
        }

        if (!empty($captchaClass = $this->getConfig('captcha'))) {
            $captcha = $container->get($captchaClass . '\\Captcha');
            $captcha->challenge($post);
        }

        // Fetch form groups
        $result = $groups->fetch([
            'where' => [ 'pluginId' => $this->getId() ]
        ]);

        $logData = [
            'formData' => [ ]
        ];

        $backmail = null;
        $recipients = [];

        foreach ($result as $group) {

            $groupData = [
                'title' => $group->getTitle(),
                'fields' => [ ]
            ];

            foreach ($group->getFields($fields) as $field) {

                $field->setValue($post->get('fields')[$field->getId()] ?? null);

                if ($field->isRequired() and $field->isEmpty()) {
                    throw new \Frootbox\Exceptions\InputMissing('Field', [ $field->getTitle() ]);
                }

                $value = $field->getValue();

                if ($field->getType() == 'Channel' and !empty($value)) {

                    foreach ($field->getChannelRecipients() as $option) {

                        if ($option['key'] == $value) {
                            $recipients[] = $option['address'];
                            break;
                        }
                    }
                }
                elseif ($field->getType() == 'File' and !empty($_SESSION['plugin'][$this->getId()]['uploads'][$field->getId()])) {

                    $value = [];

                    foreach ($_SESSION['plugin'][$this->getId()]['uploads'][$field->getId()] as $index => $fileId) {

                        $tempFile = new \Frootbox\Ext\Core\FileManager\TempFile($fileId);

                        $file = new \Frootbox\Persistence\File([
                            'sourceFile' => $tempFile->getPath(),
                            'uid' => $this->getUid('fileuploads'),
                            'name' => $tempFile->getName(),
                            'type'=> $tempFile->getType(),
                            'size' => $tempFile->getSize(),
                        ]);

                        $file = $files->insert($file);
                        $value[] = $file->getId();

                        // Remove temp file
                        $tempFile->delete();

                        // Unset upload reference
                        unset($_SESSION['plugin'][$this->getId()]['uploads'][$field->getId()][$index]);
                    }
                }

                $groupData['fields'][] = [
                    'fieldId' => $field->getId(),
                    'type' => $field->getType(),
                    'title' => $field->getTitle(),
                    'value' => $value,
                    'valueDisplay' => $field->getValueDisplay(),
                ];

                if ($backmail === null and !$field->isEmpty() and $field->getType() == 'Email') {
                    $backmail = $field->getValue();
                }
            }

            $logData['formData'][] = $groupData;
        }

        // Store form-log
        $log = $logs->insert(new \Frootbox\Ext\Core\Forms\Persistence\Log([
            'pluginId' => $this->getId(),
            'logdata' => $logData
        ]));

        // Merge dedicated recipients with channel recipients
        if (!empty($this->getConfig('recipients'))) {
            $recipients = array_merge($recipients, explode(',', $this->getConfig('recipients')));
        }

        // Prepare moderators mail
        if (!empty($recipients)) {

            $page = $this->getPage();

            $view->set('formlog', $log);
            $view->set('plugin', $this);
            $view->set('page', $page);

            $file = $this->getPath() . 'Builder/Mail.html';
            $source = $view->render($file);

            if (!empty($this->config['modSubject'])) {
                $subject = $this->config['modSubject'];
            }
            elseif (!empty($this->getTitle())) {
                $subject = 'Anfrage: ' . $this->getTitle();

                if ($page->getTitle() != $this->getTitle()) {
                    $subject .= '/' . $page->getTitle();
                }
            }
            else {
                $subject = 'Kontaktanfrage über Ihre Website';
            }

            $mail = new \Frootbox\Mail\Envelope;
            $mail->setSubject($subject);
            $mail->setBodyHtml($source);

            // Send mails
            if ($backmail !== null) {
                $mail->setReplyTo($backmail);
            }

            foreach ($recipients as $recipient) {

                $mail->clearTo();
                $mail->addTo($recipient);

                $mailTransport->send($mail);
            }
        }

        // Prepare customers mail
        if ($backmail !== null) {

            // Create new mail
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Set reply to
            if (!empty($replyTo = $this->getConfig('replyTo'))) {
                $mail->addReplyTo($this->getConfig('replyTo'));
            }

            $view->set('formlog', $log);
            $view->set('plugin', $this);

            preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

            $view->set('serverpath', SERVER_PATH_PROTOCOL);
            $view->set('baseVendor', $match[1]);
            $view->set('baseExtension', $match[2]);

            $file = $this->getPath() . 'Builder/MailCustomer.html';
            $source = $view->render($file);

            $mail = new \Frootbox\Mail\Envelope;
            $mail->setSubject($this->getConfig('customSubject') ?? 'Anfrage: ' . $this->getTitle());
            $mail->setBodyHtml($source);

            $mail->clearTo();
            $mail->addTo($backmail);

            $mailTransport->send($mail);
        }

        $payload = [];

        if (empty($this->getConfig('feedback')) or $this->getConfig('feedback') == 'NewPage') {
            $payload['redirect'] = $this->getActionUri('complete');
        }

        return new \Frootbox\View\ResponseJson($payload);
    }

    /**
     *
     */
    public function ajaxUploadAction(
        \Frootbox\Http\Get $get
    ): Response
    {
        $tempFile = \Frootbox\Ext\Core\FileManager\TempFile::createFromFile($_FILES['file']['tmp_name']);
        $tempFile->setName($_FILES['file']['name']);
        $tempFile->setType($_FILES['file']['type']);

        if (empty($_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')])) {
            $_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')] = [];
        }

        $_SESSION['plugin'][$this->getId()]['uploads'][$get->get('fieldId')][] = $tempFile->getId();

        return new \Frootbox\View\ResponseJson([

        ]);
    }


    /**
     *
     */
    public function completeAction(): Response
    {
        return new \Frootbox\View\Response();
    }

    /**
     *
     */
    public function injectCaptcha(
        \DI\Container $container
    ): string
    {
        if (empty($this->getConfig('captcha'))) {
            return (string) null;
        }

        // Obtain captcha class
        $class = $this->getConfig('captcha') . '\\Captcha';
        $captcha = $container->get($class);

        return $container->call([ $captcha, 'render' ], [
            'page' => $this->getPage(),
        ]);
    }
}
