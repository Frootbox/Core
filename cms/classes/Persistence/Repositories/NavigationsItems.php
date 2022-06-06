<?php
/**
 * @author Jan Habbo Brüning
 */

namespace Frootbox\Persistence\Repositories;

/**
 *
 */
class NavigationsItems extends \Frootbox\Db\Model
{
    protected $table = 'navigations_items';
    protected $class = \Frootbox\Persistence\NavigationItem::class;

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
