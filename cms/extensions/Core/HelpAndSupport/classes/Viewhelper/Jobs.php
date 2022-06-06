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
        $result = $jobsRepository->fetch();

        return $result;
    }
}
