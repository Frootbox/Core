<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenuTeaser;

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
    public function indexAction(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuItems $menuItemsRepository
    ): \Frootbox\View\Response
    {
        // Obtain date
        $date = date('Y-m-d');
        $date = new \Frootbox\Dates\Date($date);

        $weekday = $date->format('%u');

        $date->modify('-' . ($weekday - 1) . ' days');

        $days = [];

        // $daysLimt = !empty($plugin->getConfig('skipWeekend')) ? 5 : 7;
        $daysLimt = 5;

        for($i = 1; $i <= $daysLimt; ++$i) {

            $result = $menuItemsRepository->fetch([
                'where' => [
                    'dateStart' => $date->format('%Y-%m-%d')
                ]
            ]);

            $days[] = [
                'date' => $date->format('%Y-%m-%d'),
                'items' => $result
            ];

            $date->modify('+ 1 day');
        }

        $view->set('days', $days);

        return new \Frootbox\View\Response;
    }
}
