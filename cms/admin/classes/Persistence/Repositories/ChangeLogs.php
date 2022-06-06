<?php
/**
 *
 */

namespace Frootbox\Admin\Persistence\Repositories;

class ChangeLogs extends \Frootbox\Db\Model
{
    protected $table = 'admin_changelog';
    protected $class = \Frootbox\Admin\Persistence\ChangeLog::class;

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        $logData = $row->getLogData() ?? [];

        $row->setLogData(json_encode($logData));

        return parent::insert($row);
    }
}
