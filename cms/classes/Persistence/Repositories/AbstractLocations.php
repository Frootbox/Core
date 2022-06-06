<?php
/**
 *
 */

namespace Frootbox\Persistence\Repositories;

/**
 * Class AbstractLocations
 * @package Frootbox\Persistence\Repositories
 * @deprecated
 */
abstract class AbstractLocations extends AbstractRepository
{
    protected $table = 'locations';

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
    public function insert ( \Frootbox\Db\Row $row ): \Frootbox\Db\Row {

        $row->setClassName(get_class($row));

        return parent::insert($row);
    }
}
