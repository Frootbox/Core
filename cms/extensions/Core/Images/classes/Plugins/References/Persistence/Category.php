<?php
/**
 * @noinspection SqlNoDataSourceInspection
 */

namespace Frootbox\Ext\Core\Images\Plugins\References\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;
    protected $itemModel = Repositories\References::class;

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noCategoriesDetailPage'))) {
            return null;
        }

        $trace = $this->getTrace();
        $trace->shift();

        $vd = [ ];

        foreach ($trace as $child) {
            $vd[] = $child->getTitle();
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => $vd,
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showCategory',
                'categoryId' => $this->getId()
            ])
        ]);
    }

    /**
     *
     */
    public function getReferences(): \Frootbox\Db\Result
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
            a.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' AND
            a.className = "Frootbox\\\\Ext\\\\Core\\\\Images\\\\Plugins\\\\References\\\\Persistence\\\\Reference"
        ORDER BY
            i.orderId DESC';

        // Fetch contacts
        $model = new \Frootbox\Ext\Core\Images\Plugins\References\Persistence\Repositories\References($this->db);
        $result = $model->fetchByQuery($sql);

        return $result;
    }
}
