<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Job;

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
     * @param \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
     * @return \Frootbox\Db\Result
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
     * @param \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
     * @return \Frootbox\Db\Result
     */
    public function getAddresses(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): \Frootbox\Db\Result
    {
        if (!empty($this->plugin->getConfig('UseAllAddresses'))) {

            // Fetch addresses
            $result = $addressesRepository->fetch([
                'where' => [

                ],
                'order' => [ 'title ASC' ],
            ]);

            return $result;
        }
        else {

            // Fetch addresses
            $result = $addressesRepository->fetch([
                'where' => [
                    'pluginId' => $this->plugin->getId(),
                ],
                'order' => [ 'title ASC' ],
            ]);

            return $result;
        }
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Insert new job
        $job = $jobsRepository->insert(new \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job([
            'pageId' => $this->plugin->getPageId(),
            'pluginId' => $this->plugin->getId(),
            'title' => $post->get('title'),
            'visibility' => (DEVMODE ? 2 : 1),
        ]));

        // Trigger save to create alias
        $job->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#jobsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Index\Partials\ListJobs\Partial::class, [
                    'highlight' => $job->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\CloningMachine $cloningMachine
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxDuplicateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\CloningMachine $cloningMachine,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($get->get('jobId'));

        // Duplcate job
        $newJob = $job->duplicate();
        $newJob->setTitle($job->getTitle() . ' (Duplikat)');
        $newJob->setAlias(null);
        $newJob->save();

        $cloningMachine->cloneContentsForElement($newJob, $job->getUidBase());

        return self::getResponse('json', 200, [
            'triggerLink' => $this->plugin->getAdminUri('Job', 'details', [ 'jobId' => $newJob->getId() ]),
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(

    ): Response
    {
        return self::getResponse('plain');
    }

    /**
     *
     */
    public function ajaxSortAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ): Response
    {
        $orderId = count($get->get('row')) + 1;

        foreach ($get->get('row') as $jobId) {
            $job = $jobsRepository->fetchById($jobId);
            $job->setOrderId(--$orderId);
            $job->save();
        }

        return self::getResponse('json');
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ): Response
    {
        /**
         * Fetch job
         * @var \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Job $job
         */
        $job = $jobsRepository->fetchById($get->get('jobId'));

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        if (empty($title)) {
            throw new \Exception('Bitte alle benötigten Felder ausfüllen.');
        }

        $job->setTitle($title);
        $job->setSubtitle($post->get('subtitle'));
        $job->setDateStart(!empty($post->get('dateStart')) ? $post->get('dateStart') : null);
        $job->setLocationId(!empty($post->get('locationId')) ? $post->get('locationId') : null);

        $job->unsetConfig('titles');
        $job->unsetConfig('Types');

        $job->addConfig([
            'titles' => $post->get('titles'),
            'asSoonAsPossible' => $post->get('asSoonAsPossible'),
            'forceJobsDetailPage' => $post->get('forceJobsDetailPage'),
            'start' => $post->get('start'),
            'limitation' => $post->get('limitation'),
            'type' => $post->get('typeText'),
            'typeId' => $post->get('typeId'),
            'link' => $post->get('link'),
            'formId' => $post->get('formId'),
            'SalaryFrom' => $post->get('SalaryFrom'),
            'SalaryTo' => $post->get('SalaryTo'),
            'Types' => $post->get('Types'),
        ]);

        $job->save();

        // Set tags
        $job->setTags($post->get('tags'));

        return self::getResponse('json');
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($get->get('jobId'));

        return self::getResponse('html', 200, [
            'job' => $job
        ]);
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return self::getResponse();
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
     * @return Response
     */
    public function jumpToEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($get->get('jobId'));

        header('Location: ' . $job->getUriEdit());
        exit;
    }
}
