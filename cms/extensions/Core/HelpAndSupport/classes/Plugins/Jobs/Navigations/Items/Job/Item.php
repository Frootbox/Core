<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Navigations\Items\Job;

class Item extends \Frootbox\Ext\Core\Navigation\Navigations\Items\AbstractItem
{
    /**
     *
     */
    public function getHref(): string
    {
        if (empty($this->getConfig('jobId'))) {
            return '#unconfigured-navigation-item';
        }

        $jobsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs::class);
        $job = $jobsRepository->fetchById($this->getConfig('jobId'));

        return $job->getUri();
    }

    /**
     *
     */
    public function getJobs(): \Frootbox\Db\Result
    {
        $jobsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs::class);
        $jobs = $jobsRepository->fetch();

        return $jobs;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     * @param \Frootbox\Http\Post $post
     */
    public function updateFromPost(\Frootbox\Http\Post $post): void
    {
        $this->addConfig([
            'jobId' => $post->get('jobId'),
        ]);
    }
}
