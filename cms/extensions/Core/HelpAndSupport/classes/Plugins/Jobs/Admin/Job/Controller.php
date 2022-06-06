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
     *
     */
    public function getAddresses(
        \Frootbox\Ext\Core\Addresses\Persistence\Repositories\Addresses $addressesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $addressesRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
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
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ): Response
    {
        // Fetch job
        $job = $jobsRepository->fetchById($get->get('jobId'));

        $title = $post->get('titles')[DEFAULT_LANGUAGE] ?? $post->get('title');

        if (empty($title)) {
            throw new \Exception('Bitte alle benötigten Felder ausfüllen.');
        }

        $job->setTitle($title);
        $job->setDateStart($post->get('dateStart'));
        $job->setLocationId($post->get('locationId'));

        $job->unsetConfig('titles');
        $job->addConfig([
            'titles' => $post->get('titles'),
            'asSoonAsPossible' => $post->get('asSoonAsPossible'),
            'forceJobsDetailPage' => $post->get('forceJobsDetailPage'),
            'start' => $post->get('start'),
            'type' => $post->get('typeText'),
            'typeId' => $post->get('typeId'),
            'link' => $post->get('link'),
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
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
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
}
