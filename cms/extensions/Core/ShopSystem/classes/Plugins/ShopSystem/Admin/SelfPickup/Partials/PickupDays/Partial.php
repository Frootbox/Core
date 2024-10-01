<?php 
/**
 * 
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\SelfPickup\Partials\PickupDays;

use Frootbox\Admin\Controller\Response;

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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupDay $selfPickupDayRepository,
    ): Response
    {
        $date = $this->getData('date');
        $plugin = $this->getData('plugin');

        $dateStart = new \DateTime($date);
        $runningDate = clone $dateStart;
        $dateEnd = clone $dateStart;
        $monthNext = clone $dateStart;
        $monthPrev = clone $dateStart;

        $monthNext->modify('+1 month');
        $monthPrev->modify('-1 month');

        $dateStart->modify('first day of this month');
        $dateEnd->modify('last day of this month');

        $firstDayNum = $dateStart->format('N');
        $firstWeek = $dateStart->format('W');

        $runningDate->modify('first day of this month');
        $runningDate->modify('-' . ($firstDayNum - 1) . ' days');

        if (!empty($plugin->getConfig('shipping.skipNextWorkdays'))) {
            $firstRegularDay = new \DateTime();
            $addedDays = $plugin->getConfig('shipping.skipNextWorkdays');

            if ($firstRegularDay->format('N') == 6) {
                // $addedDays += 2;
            }
            else if ($firstRegularDay->format('N') == 7) {
                // $addedDays += 1;
            }

            $firstRegularDay->modify('+' . $addedDays . ' days');
        }


        $weeks = [];

        for ($w = 0; $w < 6; ++$w) {

            $week = [
                'nr' => (int) $runningDate->format('W'),
                'days' => [],
            ];

            for ($d = 1; $d <= 7; ++$d) {

                $isBlocked = $selfPickupDayRepository->fetchOne([
                    'where' => [
                        'dateStart' => $runningDate->format('Y-m-d') . ' 00:00:00',
                    ],
                ]);

                $regularShippingDay = !$isBlocked;

                if (isset($firstRegularDay) and $runningDate < $firstRegularDay) {
                    $regularShippingDay = false;
                }

                if (!empty($plugin->getConfig('selfPickup.regularSelfPickupDays')) and !in_array($d, $plugin->getConfig('selfPickup.regularSelfPickupDays'))) {
                    $regularShippingDay = false;
                }

                $day = [
                    'date' => $runningDate->format('Y-m-d'),
                    'today' => (date('Y-m-d') == $runningDate->format('Y-m-d')),
                    'past' => $runningDate->format('U') < $_SERVER['REQUEST_TIME'],
                    'isRegular' => $regularShippingDay,
                    'isBlocked' => $isBlocked,
                ];

                $week['days'][] = $day;

                $runningDate->modify('+ 1 day');
            }

            $weeks[] = $week;
        }

        return new Response(body: [
            'date' => $date,
            'weeks' => $weeks,
            'monthNext' => $monthNext,
            'monthPrev' => $monthPrev,
        ]);
    }
}
