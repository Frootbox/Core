<?php
/**
 *
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Viewhelper;

class ContactPersons extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'getContactPersons' => [
            'params',
        ],
    ];

    /**
     *
     */
    public function getContactPersonsAction(
        array $params = null,
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository,
    ): \Frootbox\Db\Result
    {
        if (!empty($params['tags'])) {

            $result = $contactRepository->fetchByTags($params['tags'], [
                'order' => [ 'dateStart ASC' ],
            ]);
        }
        else {

            if (empty($params['limit'])) {
                $params['limit'] = 1024;
            }

            $result = $contactRepository->fetch([
                'where' => [
                    new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
                ],
                'limit' => $params['limit'],
                'order' => [ 'orderId DESC' ],
            ]);
        }

        return $result;
    }
}
