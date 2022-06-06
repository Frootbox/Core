<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;

    /**
     * @deprecated
     */
    public function getListEntries()
    {
        return $this->getPositions();
    }

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        $trace = $this->getTrace();
        $trace->shift();

        $vd = [ ];

        foreach ($trace as $child) {
            $vd[] = $child->getTitle();
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => $vd,
            'payload' => $this->generateAliasPayload([
                'action' => 'showCategory',
                'categoryId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getPositions(): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            a.*,
            i.config as connConfig,
            i.id as connId
        FROM
            assets a,
            categories_2_items i
        WHERE
            i.categoryId = ' . $this->getId() . ' AND
            i.itemId = a.id AND
            a.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . '
        ORDER BY
            orderId DESC,
            id ASC';

        $model = new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries($this->db);
        $result = $model->fetchByQuery($sql);

        return $result;
    }
}