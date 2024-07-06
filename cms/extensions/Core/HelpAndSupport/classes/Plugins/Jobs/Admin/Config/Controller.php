<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Config;

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
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository,
    ): Response
    {
        // Set new config
        $this->plugin->addConfig($post->get('config'));

        // Update config
        $this->plugin->addConfig([
            'noJobsDetailPage' => $post->get('noJobsDetailPage'),
            'urlPrefixId' => $post->get('urlPrefixId'),
            'urlSuffixSubtitle' => $post->get('urlSuffixSubtitle'),
            'ignoreForeignTitles' => !empty($post->get('ignoreForeignTitles')),
        ]);

        $this->plugin->save();

        // Update jobs
        $result = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId(),
            ],
        ]);

        foreach ($result as $job) {

            $job->addConfig([
                'noJobsDetailPage' => $post->get('noJobsDetailPage'),
                'urlSuffixSubtitle' => $post->get('urlSuffixSubtitle'),
                'urlPrefixId' => $post->get('urlPrefixId'),
            ]);

            $job->save();
        }

        return self::getResponse('json');
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
