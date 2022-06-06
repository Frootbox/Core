<?php
/**
 *
 */

namespace Frootbox;

abstract class AbstractCronjob
{
    /**
     *
     */
    public function __construct(
        protected \Frootbox\DB\Db $db,
    )
    {

    }

    /**
     *
     */
    protected function log($code, array $data = null): void
    {
        if (!defined('USER_ID')) {
            throw new \Exception('Missing user id');
        }

        if (preg_match('#^Frootbox\\\\Ext\\\\(.*?)\\\\(.*?)\\\\Cronjobs\\\\(.*?)$#', get_class($this), $match)) {
            $code = $match[1] . '.' . $match[2] . '.Cronjobs.' . $match[3] . '.Logs.' . $code;
        }
        else {
            d("Cannot declare cron class");
        }

        // Insert new log
        $systemLogsRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\SystemLogs::class);
        $log = $systemLogsRepository->insert(new \Frootbox\Persistence\SystemLog([
            'userId' => USER_ID,
            'log_code' => $code,
            'config' => $data,
        ]));
    }
}
