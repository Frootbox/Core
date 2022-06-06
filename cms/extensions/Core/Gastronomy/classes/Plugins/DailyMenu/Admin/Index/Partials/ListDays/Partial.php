<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Admin\Index\Partials\ListDays;

/**
 * 
 */
class Partial extends \Frootbox\Admin\View\Partials\AbstractPartial
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
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\Gastronomy\Plugins\DailyMenu\Persistence\Repositories\MenuItems $menuItemsRepository
    ): void
    {
        $plugin = $this->getData('plugin');

        // Obtain date
        $date = $get->get('date') ?? date('Y-m-d');
        $date = new \Frootbox\Dates\Date($date);

        $weekday = $date->format('%u');

        $date->modify('-' . ($weekday - 1) . ' days');

        $days = [];

        $daysLimt = !empty($plugin->getConfig('skipWeekend')) ? 5 : 7;

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
    }
}
