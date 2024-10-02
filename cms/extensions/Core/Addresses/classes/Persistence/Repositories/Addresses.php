<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Ext\Core\Addresses\Persistence\Repositories;

/**
 *
 */
class Addresses extends \Frootbox\Persistence\Repositories\AbstractRepository
{
    use \Frootbox\Persistence\Repositories\Traits\Tags;

    protected $table = 'locations';
    protected $class = \Frootbox\Ext\Core\Addresses\Persistence\Address::class;

    /**
     *
     */
    public function fetch(array $params = null): \Frootbox\Db\Result
    {
        if (empty($params['where']['className'])) {
            $params['where']['className'] = $this->class;
        }

        $params['order'][] = 'orderId DESC';

        return parent::fetch($params);
    }

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        $row->setClassName(get_class($row));

        return parent::insert($row);
    }
}