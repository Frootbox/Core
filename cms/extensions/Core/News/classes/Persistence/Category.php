<?php
/**
 *
 */

namespace Frootbox\Ext\Core\News\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;
    protected $itemModel = Repositories\Articles::class;
    protected $connectionModel = Repositories\CategoriesConnections::class;
    protected $connectionClass = CategoryConnection::class;

    /**
     *
     */
    public function getItems(
        array $parameters = null
    ): \Frootbox\Db\Result
    {
        // Obtain model
        $model = $this->db->getModel($this->itemModel);

        // Build sql
        $sql = 'SELECT
            i.*,
            x.config as connConfig,
            x.id as connId,
            x.alias as alias
        FROM
            ' . $model->getTable() . ' i,
            categories_2_items x
        WHERE
            x.categoryId = ' . $this->getId() . ' AND
            x.itemId = i.id AND
            i.dateEnd >= "' . date('Y-m-d H:i:s') . '" ';

        if (!empty($parameters['order'])) {
            $sql .= ' ORDER BY ' . $parameters['order'];
        }

        $result = $model->fetchByQuery($sql);

        return $result;
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
}
