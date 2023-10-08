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
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     */
    public function insert(
        \Frootbox\Db\Row $row,
    ): \Frootbox\Db\Row
    {
        return self::persist($row);
    }

    /**
     * @param \Frootbox\Db\Row $row
     * @return \Frootbox\Db\Row
     */
    public function persist(
        \Frootbox\Db\Row $row,
    ): \Frootbox\Db\Row
    {
        // Check new uid
        if (empty($row->getUid())) {
            throw new \Exception('Mandatory UID missing from alias generation by ' . $row->getItemModel() . '.');
        }

        $row->unset('virtualDirectory');

        if (is_array($row->getPayload())) {
            $row->setPayload(json_encode($row->getPayload()));
        }

        return parent::persist($row);
    }
}
