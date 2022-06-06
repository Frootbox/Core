<?php 
/**
 * 
 */

namespace Frootbox\Persistence\Repositories;

abstract class AbstractLogs extends AbstractRepository
{
    protected $table = 'logs';
    
    /**
     *
     */
    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        $params['where']['className'] = $this->class;

        return parent::fetch($params);
    }
    
    
    /**
     *
     */
    public function insert ( \Frootbox\Db\Row $row ): \Frootbox\Db\Row
    {
        $row->setClassName(get_class($row));
        
        return parent::insert($row);
    }
}