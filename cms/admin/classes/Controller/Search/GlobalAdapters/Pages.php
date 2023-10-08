<?php
/**
 *
 */

namespace Frootbox\Admin\Controller\Search\GlobalAdapters;

class Pages
{
    /**
     *
     */
    public function getSql($keyword): string
    {
        $sql = 'SELECT
            p1.id as pageId,
            CONCAT(p2.title, " / ", p1.title) as label,
            CONCAT("fbx://page:", p1.id) as url,
            p1.alias
        FROM 
            pages p1,
            pages p2
        WHERE
            p1.title LIKE "%' . $keyword . '%" AND
            p2.id = p1.parentId                
        ORDER BY
            p1.lft ASC';

        return $sql;
    }
}
