<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Viewhelper;

class Jobs extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getJobs' => [

        ],
        'getJobsByTag' => [
            'tag'
        ],
    ];

    /**
     *
     */
    public function getJobsAction(
        array $params = null,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch jobs
        $result = $jobsRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
        ]);

        return $result;
    }

    /**
     *
     */
    public function getJobsByTagAction(
        string $tag,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch jobs
        $result = $jobsRepository->fetchByTag($tag, [
            'complyVisibility' => true,
        ]);

        return $result;
    }
}
