<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\HelpAndSupport\Plugins\ContactPersons\Viewhelper;

class ContactPersons extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected ?\Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository = null;

    protected $arguments = [
        'getContactByEmail' => [
            'params',
        ],
        'getContactPersons' => [
            'params',
        ],
    ];

    /**
     * @param \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository
     * @return void
     */
    public function onInit(
        \Frootbox\Ext\Core\HelpAndSupport\Persistence\Repositories\Contacts $contactRepository,
    ): void
    {
        $this->contactRepository = $contactRepository;
    }

    public function getContactByEmail(
        array $params = null,
    ): ?\Frootbox\Ext\Core\HelpAndSupport\Persistence\Contact
    {
        if (empty($params['email'])) {
            return null;
        }

        /**
         * Fetch contact
         * @var ?\Frootbox\Ext\Core\HelpAndSupport\Persistence\Contact $contact
         */
        $contact = $this->contactRepository->fetchOne([
            'where' => [
                'email' => $params['email'],
            ]
        ]);

        return $contact;
    }

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
