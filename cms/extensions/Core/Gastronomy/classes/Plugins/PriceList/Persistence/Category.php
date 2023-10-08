<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Tags;

    protected $model = Repositories\Categories::class;
    protected $itemModel = Repositories\ListEntries::class;

    /**
     *
     */
    public function delete()
    {
        // Clean up list entries
        $this->getItems()->map('delete');

        parent::delete();
    }

    /**
     * @deprecated
     */
    public function getListEntries(array $parameters = null)
    {
        return $this->getPositions($parameters);
    }

    /**
     * Generate articles alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noGenericDetailsPage'))) {
            return null;
        }

        if ($this->getParentId() == 0) {
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
     * @param array|null $parameters
     * @return \Frootbox\Db\Result
     */
    public function getPositions(array $parameters = null): \Frootbox\Db\Result
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
            i.itemId = a.id ';

        if (empty($parameters['ignoreVisible'])) {
            $sql .= ' AND
                a.visibility >= ' . (IS_LOGGED_IN ? 1 : 2) . ' ';
        }

        if (!IS_EDITOR and empty($parameters['ignoreDates'])) {
            $sql .= '
            AND (
                a.dateStart IS NULL OR
                a.dateStart <= "' . date('Y-m-d') . '"
            )
            AND (
                a.dateEnd IS NULL OR
                a.dateEnd >= "' . date('Y-m-d') . '"
            )
        ';
        }

        $sql .= ' ORDER BY
            orderId DESC,
            id ASC';

        $model = new \Frootbox\Ext\Core\Gastronomy\Plugins\PriceList\Persistence\Repositories\ListEntries($this->db);
        $result = $model->fetchByQuery($sql);

        return $result;
    }
}
