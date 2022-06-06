<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Forms\Plugins\Form\Admin\Archives;

use Frootbox\Admin\Controller\Response;
use Frootbox\Admin\View;
use Frootbox\Http\Get;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxDeleteLogAction(
        Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Logs $logsRepository
    ): Response
    {
        // Fetch log
        $log = $logsRepository->fetchById($get->get('logId'));

        $log->delete();

        return self::getResponse('json', 200, [
            'fadeOut' => '[data-log="' . $log->getId() . '"]'
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Logs $logsRepository,
        View $view
    ): Response
    {
        // Fetch logs
        $logs = $logsRepository->fetch([
            'where' => [
                'pluginId' => $this->plugin->getId()
            ],
            'order' => [ 'date DESC' ]
        ]);
        $view->set('logs', $logs);

        return self::getResponse();
    }

    /**
     * @param View $view
     * @param Get $get
     * @param \Frootbox\Ext\Core\Forms\Persistence\Repositories\Logs $logsRepository
     * @return Response
     */
    public function logAction(
        Get $get,
        \Frootbox\Ext\Core\Forms\Persistence\Repositories\Logs $logsRepository
    ): Response
    {
        // Fetch log
        $log = $logsRepository->fetchById($get->get('logId'));

        return self::getResponse('html', 200, [
            'log' => $log,
        ]);
    }
}
