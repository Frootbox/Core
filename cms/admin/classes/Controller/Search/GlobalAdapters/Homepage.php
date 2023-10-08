<?php
/**
 *
 */

namespace Frootbox\Admin\Controller\Search\GlobalAdapters;

class Homepage
{
    /**
     * @param $keyword
     * @return string
     */
    public function getSql($keyword): string
    {
        $sql = 'SELECT
            p1.id as pageId,
            CONCAT(p1.title) as label,
            CONCAT("fbx://page:", p1.id) as url,
            p1.alias
        FROM 
            pages p1
        WHERE
            p1.title LIKE "%' . $keyword . '%" AND
            p1.parentId = 0                
        ORDER BY
            p1.lft ASC';

        return $sql;
    }
}
