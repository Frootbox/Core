<?php
/**
 *
 */

namespace Frootbox\Admin;

use Frootbox\Session;

class Logger
{
    protected $changeLogsRepository;
    protected $user;

    /**
     *
     */
    public function __construct(
        \Frootbox\Session $session,
        \Frootbox\Admin\Persistence\Repositories\ChangeLogs $changeLogsRepository
    )
    {
        $this->changeLogsRepository = $changeLogsRepository;
        $this->user = $session->getUser();
    }

    /**
     *
     */
    public function log(
        string $action,
        array $data = null,
        \Frootbox\Db\RowInterface $object = null
    ): void
    {
        $record = [
            'action' => $action,
            'logData' => $data,
            'userId' => $this->user->getId()
        ];

        if (!empty($object)) {

            $record['itemId'] = $object->getId();
            $record['itemClass'] = get_class($object);

            if (!empty($object->getPageId())) {
                $record['pageId'] = $object->getPageId();
            }
            elseif ($object instanceof \Frootbox\Persistence\Page) {
                $record['pageId'] = $object->getId();
            }

            if (!empty($object->getPluginId())) {
                $record['pluginId'] = $object->getPluginId();
            }
        }

        $changeLog = $this->changeLogsRepository->insert(new \Frootbox\Admin\Persistence\ChangeLog($record));
    }
}
