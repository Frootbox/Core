<?php 
/**
 * 
 */

namespace Frootbox\Persistence\Repositories;

abstract class AbstractRepository extends \Frootbox\Db\Model
{
    /**
     *
     */
    public function fetchById($rowId): \Frootbox\Db\Row
    {
        if (is_string($rowId) and $rowId[0] == 'p') {
            $row = require FILES_DIR . 'persistency/' . $rowId . '.php';
        
            return $row;
        }
        
        return parent::fetchById($rowId);
    }

    /**
     *
     */
    public function insert(\Frootbox\Db\Row $row): \Frootbox\Db\Row
    {
        $row = parent::insert($row);

        if (method_exists($row, 'getNewAlias')) {

            if ($row instanceof \Frootbox\Persistence\Alias) {
                d("stop now :-)");
            }

            $row->save();
        }

        return $row;
    }
}
