<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\JobsTeaser;

use Frootbox\Db\Result;
use Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs;
use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
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
    public function getJobs(
        Jobs $jobsRepository,
        $order = null,
    ): Result
    {
        $limit = !empty($this->getConfig('limit')) ? $this->getConfig('limit') : 1024;

        if (empty($order)) {
            $order = 'orderId DESC, date DESC';
        }

        // Fetch jobs
        $result = $jobsRepository->fetch([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
            ],
            'order' => [
                $order,
            ],
            'limit' => $limit,
        ]);

        return $result;
    }

    /**
     *
     */
    public function indexAction(

    ): Response
    {
        return new Response([

        ]);
    }
}
