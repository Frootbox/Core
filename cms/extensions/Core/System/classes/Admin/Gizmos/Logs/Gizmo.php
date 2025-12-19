<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\System\Admin\Gizmos\Logs;

use Frootbox\Admin\Controller\Response;

class Gizmo extends \Frootbox\Admin\AbstractGizmo
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
    public function onBeforeRendering(
        \Frootbox\Admin\View $view,
        \Frootbox\Persistence\Repositories\SystemLogs $logRepository,
    ): ?Response
    {

        $result = $logRepository->fetch([
            'order' => [ 'date DESC' ],
            'limit' => 5,
        ]);


        $view->set('logs', $result);

        return self::getResponse();
    }
}
