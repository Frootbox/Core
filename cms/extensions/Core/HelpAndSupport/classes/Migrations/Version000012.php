<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Migrations;

class Version000012 extends \Frootbox\AbstractMigration
{
    protected $description = 'Aktualisiert alle Jobs auf die neue URL.';

    /**
     *
     */
    public function up(
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): void
    {
        // Fetch all jobs
        $result = $jobsRepository->fetch([

        ]);

        foreach ($result as $job) {

            try {
                $job->save();
            }
            catch ( \Exception $e ) {
                d($e);
            }
        }
    }
}
