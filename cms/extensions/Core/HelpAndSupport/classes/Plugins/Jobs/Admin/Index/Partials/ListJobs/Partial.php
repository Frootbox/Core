<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Admin\Index\Partials\ListJobs;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
{
    /**
     * 
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function onBeforeRendering (
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\HelpAndSupport\Plugins\Jobs\Persistence\Repositories\Jobs $jobsRepository
    ) {
        // Fetch plugin
        $plugin = $this->getData('plugin');

        $result = $jobsRepository->fetch([
            'where' => [
                'pluginId' => $plugin->getId(),
            ],
            'order' => [ 'orderId DESC'],
        ]);

        $view->set('jobs', $result);
    }
}
