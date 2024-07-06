<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs;

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
    public function setPageId(int $pageId): void
    {
        // Fetch jobs
        $jobsRepository = $this->getDb()->getRepository(Persistence\Repositories\Jobs::class);
        $jobs = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
            ],
        ]);

        foreach ($jobs as $job) {

            $job->setPageId($pageId);
            $job->save();
        }

        parent::setPageId($pageId);
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
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Address $location
     * @return \Frootbox\Db\Result
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getJobsByLocation(\Frootbox\Ext\Core\Addresses\Persistence\Address  $location): \Frootbox\Db\Result
    {
        // Fetch jobs
        $repository = $this->getDb()->getRepository(Persistence\Repositories\Jobs::class);
        $jobs = $repository->fetch([
            'where' => [
                'pluginId' => $this->getId(),
                'locationId' => $location->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', (IS_LOGGED_IN ? 1 : 2)),
            ],
        ]);

        return $jobs;
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

    public function ajaxSearchAction(
        \Frootbox\Http\Post $post,
        Persistence\Repositories\Jobs $jobRepository,
    ): Response
    {
        $sql = 'SELECT
            j.*
        FROM
             assets j
        WHERE        
            j.pluginId = ' . $this->getId() . ' AND
            j.className = :className AND
            j.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND
            (
                j.title LIKE :keyword                
            )
        ORDER BY j.title ASC';

        $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
        $result = $addressesRepository->fetchByQuery($sql, [
            'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job::class,
            'keyword' => '%' . $post->get('keyword') . '%',
        ]);

        d($result);
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
     * @param Persistence\Repositories\Jobs $jobsRepository
     * @return Response
     */
    public function indexAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $configuration,
        Persistence\Repositories\Jobs $jobRepository,
    ): Response
    {
        if (!empty($get->get('keyword')) or !empty($get->get('jobTypeId')) or !empty($get->get('fieldOfActivity'))) {

            $algoliaController = new \Frootbox\Ext\PixelFabrikLib\AlgoliaSearch\ExtensionController;

            require $algoliaController->getPath() . 'vendor/autoload.php';

            // Initialize algolia client
            $client = \Algolia\AlgoliaSearch\SearchClient::create(
                $configuration->get('Ext.PixelFabrikLib.AlgoliaSearch.appId'),
                $configuration->get('Ext.PixelFabrikLib.AlgoliaSearch.apiKey')
            );


            $tagFilters[] = 'Asset/Ext/Core/HelpAndSupport/Job';
            $filters = '';

            if (!empty($get->get('jobTypeId'))) {

                if ($get->get('jobTypeId') == 'Fulltime') {
                    $tagFilters[] = [ 'Fulltime', 'FulltimeOrParttime' ];
                }
                elseif ($get->get('jobTypeId') == 'Parttime') {
                    $tagFilters[] = [ 'Parttime', 'FulltimeOrParttime' ];
                }
                elseif ($get->get('jobTypeId') == 'FulltimeOrParttime') {
                    $tagFilters[] = [ 'Fulltime', 'Parttime', 'FulltimeOrParttime' ];
                }
                else {
                    $tagFilters[] = $get->get('jobTypeId');
                }
            }

            if (!empty($get->get('fieldOfActivity'))) {
                $tagFilters[] = 'tag-' . $get->get('fieldOfActivity');
                $filters .= (!empty($filters) ? ' AND ' : '') . '_tags:tag-' . $get->get('fieldOfActivity');
            }

            // $filters .= ' AND _tags:' . GLOBAL_LANGUAGE;

            $index = $client->initIndex('Website');

            $result = $index->search($get->get('keyword'), [
                'tagFilters' => $tagFilters,
              //  'filters' => $filters,
                'attributesToRetrieve' => [
                    'title',
                    'url',
                    'categories'
                ],
                'attributesToSnippet' => [
                    "context:20",
                ],
                'hitsPerPage' => 1000,
                'page' => $get->get('page') ?? 0,
            ]);

            $list = [];

            foreach ($result['hits'] as $job) {

                preg_match('#:(\d+)$#', $job['objectID'], $match);

                try {
                    $list[] = $jobRepository->fetchById($match[1]);
                }
                catch ( \Frootbox\Exceptions\NotFound $e ) {
                    // d($e);
                }
            }

            $result = $list;
            /*
            // Fetch jobs
            $result = $jobRepository->fetch([
                'where' => [
                    'pluginId' => $this->getId(),
                    new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
                ],
                'order' => [ 'isSticky DESC', 'orderId DESC' ],
            ]);
            */
        }
        else {

            $sql = 'SELECT
                j.*
            FROM
                 assets j
            WHERE        
                j.pluginId = ' . $this->getId() . ' AND
                j.className = :className AND
                j.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND
                (
                    j.title LIKE :keyword OR
                    j.subtitle LIKE :keyword       
                )
            ORDER BY
                j.isSticky DESC,
                j.orderId DESC';


            $addressesRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses::class);
            $result = $addressesRepository->fetchByQuery($sql, [
                'className' => \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job::class,
                'keyword' => '%' . $post->get('keyword') . '%',
            ]);
        }

        if (MULTI_LANGUAGE and DEFAULT_LANGUAGE != GLOBAL_LANGUAGE and $this->getConfig('ignoreForeignTitles')) {

            foreach ($result as $index => $job) {

                $aliases  = json_decode($job->getDataRaw('aliases'), true);

                if (empty($aliases['index'][GLOBAL_LANGUAGE])) {
                    $result->removeByIndex($index);
                }
            }
        }

        return new \Frootbox\View\Response([
            'keyword' => $post->get('keyword'),
            'jobs' => $result,
        ]);
    }

    /**
     * @param Persistence\Repositories\Jobs $jobRepository
     * @return Response
     */
    public function showJobAction(
        Persistence\Repositories\Jobs $jobRepository,
    ): Response
    {
        // Fetch job
        $job = $jobRepository->fetchById($this->getAttribute('jobId'));

        if (!$job->isVisible()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new \Frootbox\View\Response([
            'maxUploadSize' => round(\Frootbox\Persistence\Repositories\Files::getUploadMaxSize() / 1024 / 1024, 2),
            'job' => $job
        ]);
    }
}
