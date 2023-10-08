<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Schedule\Viewhelper;

class Events extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getEvents' => [
            'params',
        ],
    ];

    /**
     * Generate booking url
     */
    public function getBookingPluginAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementRepository,
    ): ?\Frootbox\Ext\Core\Events\Plugins\Booking\Plugin
    {
        $plugin = $contentElementRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\Events\Plugins\Booking\Plugin::class,
            ],
        ]);

        return $plugin;
    }

    /**
     *
     */
    public function getEventsAction(
        array $params = null,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository,
    ): \Frootbox\Db\Result
    {
        if (!empty($params['tags'])) {
            $params['tags'] = array_filter($params['tags'], fn($value) => !is_null($value) && $value !== '');
        }

        if (!empty($params['tags'])) {

            $result = $eventsRepository->fetchByTags($params['tags'], [
                'order' => [ 'dateStart ASC' ],
                'where' => [
                    new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
                    'or' => [
                        new \Frootbox\Db\Conditions\Greater('dateStart', date('Y-m-d H:i:s')),
                        new \Frootbox\Db\Conditions\Greater('dateEnd', date('Y-m-d H:i:s')),
                    ],
                ],
            ]);
        }
        else {

            if (empty($params['limit'])) {
                $params['limit'] = 10;
            }

            $where = [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
                'or' => [
                    new \Frootbox\Db\Conditions\Greater('dateStart', date('Y-m-d H:i:s')),
                    new \Frootbox\Db\Conditions\Greater('dateEnd', date('Y-m-d H:i:s')),
                ],
            ];

            if (!empty($params['pluginId'])) {
                $where[] = new \Frootbox\Db\Conditions\Equal('pluginId', (int) $params['pluginId']);
            }

            $result = $eventsRepository->fetch([
                'limit' => $params['limit'],
                'order' => [ 'dateStart ASC' ],
                'where' => $where,
            ]);
        }

        return $result;
    }
}
