<?php 
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 * 
 */
class SystemLogs extends \Frootbox\Db\Model
{
    protected $table = 'system_log';
    protected $class = \Frootbox\Persistence\SystemLog::class;
}
