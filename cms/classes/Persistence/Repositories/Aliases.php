<?php
/**
 *
 */

namespace Frootbox\Persistence\Repositories;

class Aliases extends \Frootbox\Db\Model
{
    protected $table = 'aliases';
    protected $class = \Frootbox\Persistence\Alias::class;

    /**
     *
     */
    public function insert(
        \Frootbox\Db\Row $row,
    ): \Frootbox\Db\Row
    {
        $row->unset('virtualDirectory');

        if (is_array($row->getPayload())) {
            $row->setPayload(json_encode($row->getPayload()));
        }

        return parent::insert($row);
    }
}
