<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\SelfPickup;

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
     * @return Response
     */
    public function ajaxModalConfigurationAction(

    ): Response
    {
        return self::getResponse('plain', 200, [

        ]);
    }

    /**
     * @return Response
     */
    public function ajaxModalTimeComposeAction(

    ): Response
    {
        return self::getResponse('plain', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxUpdateConfigurationAction(
        \Frootbox\Http\Post $post,
    ): Response
    {
        $this->plugin->unsetConfig('selfPickup');
        $this->plugin->addConfig([
            'selfPickup' => $post->get('selfPickup'),
        ]);
        $this->plugin->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return \Frootbox\Admin\Controller\Response
     */
    public function ajaxTimeCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Compose new pickup time
        $pickUpTime = new \Frootbox\Ext\Core\ShopSystem\Persistence\SelfPickupTime([
            'pluginId' => $this->plugin->getId(),
            'dateStart' => '2000-01-01 ' . $post->get('PickUpTimeStart'),
            'dateEnd' => '2000-01-01 ' . $post->get('PickUpTimeEnd'),
            'config' => [
                'LeadTime' => $post->get('LeadTime'),
            ],
        ]);

        // Persist new pickup time
        $selfPickupTimeRepository->persist($pickUpTime);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#pickup-time-receiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\SelfPickup\Partials\PickupTimes\Partial::class, [
                    'Plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository
     * @param \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
     * @return \Frootbox\Admin\Controller\Response
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function ajaxTimeUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch time
        $pickUpTime = $selfPickupTimeRepository->fetchById($get->get('PickUpTimeId'));

        // Update pickup-time
        $pickUpTime->setDateStart('2000-01-01 ' . $post->get('PickUpTimeStart'));
        $pickUpTime->setDateEnd('2000-01-01 ' . $post->get('PickUpTimeEnd'));
        $pickUpTime->addConfig([
            'LeadTime' => $post->get('LeadTime'),
            'Weekdays' => $post->get('Weekdays'),
        ]);

        $pickUpTime->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#pickup-time-receiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\SelfPickup\Partials\PickupTimes\Partial::class, [
                    'Plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    public function ajaxModalTimeEditAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickupTimeRepository,
    ): Response
    {
        // Fetch time
        $pickUpTime = $selfPickupTimeRepository->fetchById($get->get('PickupTimeId'));

        return self::getResponse('plain', 200, [
            'PickUpTime' => $pickUpTime,
        ]);
    }

    /**
     *
     */
    public function ajaxToggleDayAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupDay $selfPickupDayRepository,
    ): Response
    {
        // Validate date
        $date = new \DateTime($get->get('date'));

        if ($date < new \DateTime()) {
            throw new \Exception('Es können keine vergangenen Daten bearbeitet werden.');
        }

        $check = $selfPickupDayRepository->fetchOne([
            'where' => [
                'dateStart' => $get->get('date') . ' 00:00:00',
            ],
        ]);

        if (!empty($check)) {

            // Free date
            $check->delete();

            return self::getResponse('json', 200, [
                'success' => 'Die Daten wurden gespeichert.',
                'removeClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'blocked',
                ],
                'addClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'active',
                ],
            ]);
        }
        else {

            // Block date
            $shippingDay = new \Frootbox\Ext\Core\ShopSystem\Persistence\SelfPickupDay([
                'dateStart' => $get->get('date'),
            ]);

            $selfPickupDayRepository->insert($shippingDay);

            return self::getResponse('json', 200, [
                'success' => 'Die Daten wurden gespeichert.',
                'addClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'blocked',
                ],
            ]);
        }
    }

    /**
     * @param \Frootbox\Http\Get $get
     * @return Response
     */
    public function indexAction(
        \Frootbox\Http\Get $get,
    ): Response
    {
        $date = $get->get('date') ? $get->get('date') : date('Y-m-d');

        return self::getResponse(body: [
            'date' => $date,
        ]);
    }
}
