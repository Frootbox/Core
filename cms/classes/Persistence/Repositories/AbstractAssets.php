<?php 
/**
 * 
 */

namespace Frootbox\Persistence\Repositories;

use Frootbox\Db\Row;

abstract class AbstractAssets extends AbstractRepository
{
    protected $table = 'assets';

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
        Row $row
    ): Row
    {
        if (empty($row->getClassName())) {
            $row->setClassName(get_class($row));
        }

        return parent::insert($row);
    }
}
