<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ContactForms\StaticPages\Form;

class Page extends \Frootbox\AbstractStaticPage
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
    public function ajaxUpload(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\View\ResponseJson
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        if (!empty($field->getConfig('maxSize')) and (($_FILES['file']['size'] / 1024 / 1024) > (float) $field->getConfig('maxSize'))) {
            throw new \Exception('Maximale Größe überschritte.');
        }

        if (!$field instanceof \Frootbox\Ext\Core\ContactForms\Persistence\Fields\Files\Field) {
            throw new \Exception('Field-Type Mismatch.');
        }

        $tempFile = \Frootbox\Ext\Core\FileManager\TempFile::createFromFile($_FILES['file']['tmp_name']);
        $tempFile->setName($_FILES['file']['name']);
        $tempFile->setType($_FILES['file']['type']);

        if (empty($_SESSION['contactforms'][$field->getId()]['uploads'])) {
            $_SESSION['contactforms'][$field->getId()]['uploads'] = [];
        }

        $_SESSION['contactforms'][$field->getId()]['uploads'][] = $tempFile->getId();

        return new \Frootbox\View\ResponseJson([

        ]);
    }

    /**
     *
     */
    public function ajaxFileRemove(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\View\Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        if (!empty($_SESSION['contactforms'][$field->getId()]['uploads'])) {

            foreach ($_SESSION['contactforms'][$field->getId()]['uploads'] as $index => $fileId) {
                if ($fileId == $get->get('fileId')) {
                    unset($_SESSION['contactforms'][$field->getId()]['uploads'][$index]);
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
    public function ajaxFilesBucket(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Fields $fieldsRepository
    ): \Frootbox\View\Response
    {
        // Fetch field
        $field = $fieldsRepository->fetchById($get->get('fieldId'));

        if (!$field instanceof \Frootbox\Ext\Core\ContactForms\Persistence\Fields\Files\Field) {
            throw new \Exception('Field-Type Mismatch.');
        }

        $list = [];

        if (!empty($_SESSION['contactforms'][$field->getId()]['uploads'])) {

            foreach ($_SESSION['contactforms'][$field->getId()]['uploads'] as $tempId) {

                $list[] = new \Frootbox\Ext\Core\FileManager\TempFile($tempId);
            }
        }

        return new \Frootbox\View\Response([
            'field' => $field,
            'files' => $list
        ]);
    }

    /**
     * 
     */
    public function ajaxSubmit (
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Files $files,
        \Frootbox\Persistence\Repositories\Pages $pageRepository,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Logs $logs,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository,
    ): \Frootbox\View\ResponseJson
    {
        $xparams = $post->get('_xparams') ?? [];

        // Fetch form
        $form = $formsRepository->fetchById($get->get('formId'));

        if (!empty($config->get('recaptcha.v3.key'))) {

            $payload = [
                'secret' => $config->get('recaptcha.v3.secret'),
                'response' => $post->get('token'),
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $body = curl_exec($curl);

            $response = json_decode($body, true);

            if (!empty($response['error-codes']) or $response['score'] < 0.5) {
                throw new \Exception('Das Formular konnte leider nicht gesendet werden.');
            }
        }

        $addedPayload = (!empty($post->get('addedPayload')) ? json_decode($post->get('addedPayload'), true) : []);

        // Fetch form groups
        $groups = $form->getGroups();

        $logData = [
            'addedPayload' => $addedPayload,
            'formData' => [ ],
        ];

        $backmail = null;
        $backmailFallback = null;
        $recipients = [];
        $attachments = [];
        $autoAttachments = [];

        foreach ($groups as $group) {

            $groupData = [
                'title' => $group->getTitle(),
                'fields' => [ ],
            ];

            $atLeastOneFieldIsFilled = false;

            foreach ($group->getFields() as $field) {

                if ($field->isSkippedInLog()) {
                    continue;
                }

                $field->setValue($post->get('fields')[$field->getId()] ?? null);

                if ($field->isRequired() and $field->isEmpty()) {
                    throw new \Frootbox\Exceptions\InputMissing('Field', [ $field->getTitle() ]);
                }

                if (!$field->isEmpty()) {
                    $atLeastOneFieldIsFilled = true;
                }

                $value = $field->getValue();

                if ($field->getType() == 'Checkbox' and !empty($field->getValue())) {

                    // Add auto attachments
                    $autoAttachmentsFromField = $files->fetch([
                        'where' => [
                            'uid' => $field->getUid('autoAttachments'),
                        ],
                    ]);

                    foreach ($autoAttachmentsFromField as $file) {
                        $autoAttachments[] = $file;
                    }
                }

                if ($field->getType() == 'Channel' and !empty($value)) {

                    $lines = explode("\n", $field->getConfig('options'));
                    foreach ($lines as $line) {
                        if (strpos($line, $value) === 0) {
                            $da = explode(':', $line);
                            $recipients[] = $da[1] ?? $da[0];
                            break;
                        }
                    }
                }
                elseif ($field->getType() == 'Files' and !empty($_SESSION['contactforms'][$field->getId()]['uploads'])) {

                    $value = [];

                    foreach ($_SESSION['contactforms'][$field->getId()]['uploads'] as $index => $fileId) {

                        $tempFile = new \Frootbox\Ext\Core\FileManager\TempFile($fileId);

                        $file = new \Frootbox\Persistence\File([
                            'sourceFile' => $tempFile->getPath(),
                            'uid' => $form->getUid('fileuploads'),
                            'name' => $tempFile->getName(),
                            'type'=> $tempFile->getType(),
                            'size' => $tempFile->getSize(),
                        ]);

                        $file = $files->insert($file);
                        $value[] = [
                            'id' => $file->getId(),
                            'name' => $file->getName(),
                        ];

                        $attachments[] = $tempFile;
                    }

                    // Unset upload reference
                    unset($_SESSION['contactforms'][$field->getId()]['uploads']);

                    $field->setFiles($value);
                }

                $fieldType = $field->getType();

                if ($field instanceof \Frootbox\Ext\Core\ContactForms\Persistence\Fields\Text\Field and !empty($field->getConfig('captureAutoBackmail'))) {
                    if (filter_var($field->getValue(), FILTER_VALIDATE_EMAIL)) {
                        $backmailFallback = $field->getValue();
                        $fieldType = 'Email';
                    }
                }

                $groupData['fields'][] = [
                    'fieldId' => $field->getId(),
                    'type' => $fieldType,
                    'className' => get_class($field),
                    'title' => $field->getTitle(),
                    'value' => $value,
                    'valueDisplay' => $field->getValueDisplay(),
                ];

                if ($backmail === null and !$field->isEmpty() and $field->getType() == 'Email') {
                    $backmail = $field->getValue();
                }
            }

            if (!empty($group->getConfig('oneFieldIsRequired')) and !$atLeastOneFieldIsFilled) {
                throw new \Frootbox\Exceptions\InputMissing('GroupFields');
            }

            $logData['formData'][] = $groupData;
        }

        // Merge dedicated recipients with channel recipients
        if (!empty($form->getConfig('recipients'))) {
            $recipients = array_merge($recipients, explode(',', $form->getConfig('recipients')));
        }

        $logData['recipients'] = $recipients;

        // Store form-log
        $log = $logs->insert(new \Frootbox\Ext\Core\ContactForms\Persistence\Log([
            'parentId' => $form->getId(),
            'logdata' => $logData
        ]));

        $extensionController = new \Frootbox\Ext\Core\ContactForms\ExtensionController;

        // Prepare moderators mail
        if (!empty($recipients)) {

            $view->set('formlog', $log);
            $view->set('form', $form);

            $file = $extensionController->getPath() . 'resources/private/views/builder/Mail.html.twig';

            $source = $view->render($file);

            if (!empty($form->getConfig('modSubject'))) {
                $subject = $form->getConfig('modSubject');
            }
            elseif (!empty($form->getTitle())) {
                $subject = 'Anfrage: ' . $form->getTitle();
            }
            else {
                $subject = 'Kontaktanfrage über Ihre Website';
            }

            $mail = new \Frootbox\Mail\Envelope;
            $mail->setSubject($subject);
            $mail->setBodyHtml($source);

            // Add attachments
            foreach ($attachments as $tempFile) {
                $attachment = new \Frootbox\Mail\Attachment($tempFile->getPath(), $tempFile->getName());
                $mail->addAttachment($attachment);
            }

            // Add auto attachments
            $autoAttachmentsFromForm = $files->fetch([
                'where' => [
                    'uid' => $form->getUid('autoAttachments'),
                ],
            ]);

            foreach ($autoAttachmentsFromForm as $file) {
                $autoAttachments[] = $file;
            }

            foreach ($autoAttachments as $file) {
                $attachment = new \Frootbox\Mail\Attachment(FILES_DIR . $file->getPath(), $file->getName());
                $mail->addAttachment($attachment);
            }

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

        if ($backmail === null and $backmailFallback !== null) {
            $backmail = $backmailFallback;
        }

        // Prepare customers mail
        if (empty($form->getConfig('autoReplyDeaktivated')) and $backmail !== null) {

            // Create new mail
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Set reply to
            if (!empty($replyTo = $form->getConfig('replyTo'))) {
                $mail->addReplyTo($replyTo);
            }

            $view->set('formlog', $log);
            $view->set('form', $form);

            $view->set('serverpath', SERVER_PATH_PROTOCOL);

            if (preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match)) {
                $view->set('baseVendor', $match[1]);
                $view->set('baseExtension', $match[2]);
            }

            $file = $extensionController->getPath() . 'resources/private/views/builder/MailCustomer.html.twig';
            $source = $view->render($file);

            $mail = new \Frootbox\Mail\Envelope;
            $mail->setSubject($form->getConfig('customSubject') ?? 'Anfrage: ' . $form->getTitle());

            $mail->setBodyHtml($source);

            $mail->clearTo();
            $mail->addTo($backmail);

            $mailTransport->send($mail, [
                'inlineImages' => true,
            ]);
        }

        $payload = [];

        foreach ($attachments as $tempfile) {
            $tempfile->delete();
        }


        if (!empty($xparams['redirect'])) {
            $payload['redirect'] = $xparams['redirect'];
        }
        elseif (empty($form->getConfig('feedback')) or $form->getConfig('feedback') == 'Page') {

            if (!empty($get->get('pluginId'))) {

                $plugin = $contentElements->fetchById($get->get('pluginId'));
                $payload['redirect'] = $plugin->getActionUri('complete');
            }
            elseif($form->getConfig('feedbackPageId')) {

                try {

                    $page = $pageRepository->fetchById($form->getConfig('feedbackPageId'));

                    $payload['redirect'] = $page->getUri([
                        'absolute' => true,
                    ]);
                }
                catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        if ($form->getConfig('feedback') == 'Callback' and !empty($form->getConfig('callback'))) {
            $payload['callback'] = $form->getConfig('callback');
        }

        return new \Frootbox\View\ResponseJson($payload);
    }
}
