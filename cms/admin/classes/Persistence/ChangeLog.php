<?php
/**
 *
 */

namespace Frootbox\Admin\Persistence;

class ChangeLog extends \Frootbox\Db\Row
{
    protected $table = 'admin_changelog';
    protected $model = Repositories\ChangeLogs::class;
}
