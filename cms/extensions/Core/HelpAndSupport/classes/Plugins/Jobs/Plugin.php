<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs;

use Frootbox\Http\Interfaces\ResponseInterface;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    protected $publicActions = [
        'index',
        'showJob',
    ];

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeDelete(
        Persistence\Repositories\Jobs $jobsRepository,
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): void
    {
        // Fetch jobs
        $jobs = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $jobs->map('delete');

        // Fetch locations
        $addresses = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        $addresses->map('delete');
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

        $jobsRepository = $container->get(Persistence\Repositories\Jobs::class);
        $rows = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $ancestor->getId(),
            ],
        ]);

        foreach ($rows as $row) {

            $newRow = $row->duplicate();
            $newRow->setPluginId($this->getId());
            $newRow->setPageId($this->getPage()->getId());
            $newRow->setAlias(null);
            $newRow->save();

            $cloningMachine->cloneContentsForElement($newRow, $row->getUidBase());
        }
    }

    /**
     *
     */
    public function getMailSubject(Persistence\Job $job): string
    {
        if (empty($this->getConfig('customEmailSubject'))) {
            return $job->getTitle();
        }

        $subject = $this->getConfig('customEmailSubject');
        $subject = str_replace('[title]', $job->getTitle(), $subject);

        return $subject;
    }

    /**
     *
     */
    public function getLocationsAvailable(): \Frootbox\Db\Result
    {
        $sql = 'SELECT
            l.*,
            COUNT(l.id) as jobsCount
        FROM
             locations l,
             assets j
        WHERE
            l.pluginId = ' . $this->getId() . ' AND                
            j.className = :className AND
            j.locationId = l.id AND
            j.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND
            l.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . '                            
        GROUP BY
            l.id
        ORDER BY jobsCount DESC';

        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
        $result = $addressesRepository->fetchByQuery($sql, [
            'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job::class,
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
        $list = [];

        if (!empty($_SESSION['plugin'][$this->getId()]['uploads'])) {

            foreach ($_SESSION['plugin'][$this->getId()]['uploads'] as $tempId) {

                $list[] = new \Frootbox\Ext\Core\FileManager\TempFile($tempId);
            }
        }

        return new \Frootbox\View\ResponseView([
            'files' => $list,
        ]);
    }

    /**
     *
     */
    public function ajaxJobsListAction(
        \Frootbox\Http\Get $get,
        \DI\Container $container,
        Persistence\Repositories\Jobs $jobsRepository,
    ): \Frootbox\View\ResponseJson
    {

        // Build sql
        $sql = 'SELECT
            j.*
        FROM
            assets j';

        if (!empty($get->get('locations'))) {
            $sql .= ', locations l ';
        }

        $sql .= ' WHERE
            j.pluginId = ' . $this->getId() . ' AND                
            j.className = :className AND            
            j.visibility >= ' . (IS_LOGGED_IN ? 1 : 2);

        if (!empty($get->get('locations'))) {
            $sql .= ' AND 
                j.locationId = l.id AND
                l.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND
                l.id IN (' . implode(',', array_map('intval', $get->get('locations'))) . ') ';
        }

        $sql .= ' ORDER BY
            j.orderId DESC';

        // Fetch jobs
        $result = $jobsRepository->fetchByQuery($sql, [
            'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job::class,
        ]);

        $html = $container->call([ $this, 'renderHtml' ], [
            'action' => 'ajaxJobsList',
            'variables' => [
                'jobs' => $result,
            ],
        ]);

        $parser = new \Frootbox\View\HtmlParser($html, $container);
        $html = $container->call([ $parser, 'parse' ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $html,
        ]);
    }

    /**
     *
     */
    public function ajaxRemoveTempFileAction(
        \Frootbox\Http\Get $get,
    ): Response
    {
        if (!empty($_SESSION['plugin'][$this->getId()]['uploads'])) {

            foreach ($_SESSION['plugin'][$this->getId()]['uploads'] as $index => $fileId) {
                if ($fileId == $get->get('fileId')) {
                    unset($_SESSION['plugin'][$this->getId()]['uploads'][$index]);
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
        \Frootbox\Http\Post $post,
        Persistence\Repositories\Jobs $jobsRepository,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($this->getAttribute('jobId'));

        $mail = new \Frootbox\Mail\Envelope;

        $recipients = explode(',', $this->getConfig('recipients'));

        foreach ($recipients as $recipient) {
            $mail->addTo($recipient);
        }

        $subject = 'Anfrage Jobangebot: ' . $job->getTitle();

        if ($location = $job->getLocation()) {
            $subject .= ' / ' . $location->getTitle();
        }

        $mail->setSubject($subject);

        $file = $this->getPath() . 'Builder/Mail.html.twig';
        $source = $view->render($file, [
            'plugin' => $this,
            'data' => [
                'name' => $post->get('name'),
                'email' => $post->get('email'),
                'description' => $post->get('description'),
            ],
            'job' => $job,
        ]);

        $mail->setBodyHtml($source);
        $mail->setReplyTo($post->get('email'));

        $attachments = [];

        if (!empty($_SESSION['plugin'][$this->getId()]['uploads'])) {

            foreach ($_SESSION['plugin'][$this->getId()]['uploads'] as $tempId) {

                $tempfile = new \Frootbox\Ext\Core\FileManager\TempFile($tempId);

                $attachment = new \Frootbox\Mail\Attachment($tempfile->getPath(), $tempfile->getName());
                $attachments[] = $tempfile;

                $mail->addAttachment($attachment);
            }
        }

        $mailTransport->send($mail);

        // Send mail to applier
        if (!empty($post->get('email'))) {

            $mail->clearTo();
            $mail->setReplyTo(null);
            $mail->addTo($post->get('email'));

            $mailTransport->send($mail);
        }

        foreach ($attachments as $tempfile) {
            $tempfile->delete();
        }

        $_SESSION['plugin'][$this->getId()]['uploads'] = [];

        return new \Frootbox\View\ResponseJson([
            'redirect' => $this->getActionUri('completed'),
        ]);
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

        if (empty($_SESSION['plugin'][$this->getId()]['uploads'])) {
            $_SESSION['plugin'][$this->getId()]['uploads'] = [];
        }

        $_SESSION['plugin'][$this->getId()]['uploads'][] = $tempFile->getId();

        return new \Frootbox\View\ResponseJson([

        ]);
    }

    /**
     *
     */
    public function indexAction(
        Persistence\Repositories\Jobs $jobsRepository
    ): Response
    {
        // Fetch jobs
        $result = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
            ],
        ]);

        return new \Frootbox\View\Response([
            'jobs' => $result
        ]);
    }

    /**
     *
     */
    public function showJobAction(
        Persistence\Repositories\Jobs $jobsRepository
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($this->getAttribute('jobId'));

        return new \Frootbox\View\Response([
            'maxUploadSize' => round(\Frootbox\Persistence\Repositories\Files::getUploadMaxSize() / 1024 / 1024, 2),
            'job' => $job
        ]);
    }
}
