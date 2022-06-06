<?php 
/**
 * 
 */

namespace Frootbox\Persistence\Repositories;

abstract class AbstractPersons extends AbstractRepository
{
    protected $table = 'persons';

    /**
     *
     */
    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        $params['where']['className'] = $this->class;
        $params['order'][] = 'orderId DESC';

        return parent::fetch($params);
    }

    /**
     *
     */
    public function insert(
        \Frootbox\Db\Row $row
    ): \Frootbox\Db\Row
    {
        $row->setClassName(get_class($row));
        
        return parent::insert($row);
    }
}
