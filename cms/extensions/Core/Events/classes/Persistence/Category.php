<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Persistence;

class Category extends \Frootbox\Persistence\Category
{
    use \Frootbox\Persistence\Traits\Alias;
    
    protected $model = Repositories\Categories::class;
   # protected $connectionModel = Repositories\CategoriesConnections::class;
   # protected $connectionClass = CategoryConnection::class;
    protected $itemModel = Repositories\Events::class;

    /**
     *
     */
    public function getEventsGroupedByMonth(): array
    {
        $events = $this->getItemsVisible([
            'order' => 'dateStart ASC',
        ]);

        $months = [];

        foreach ($events as $event) {

            $monthKey = $event->getDateStart()->format('%Y-%m');

            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'month' => [
                        'date' => $monthKey . '-01',
                    ],
                    'events' => [],
                ];
            }

            $months[$monthKey]['events'][] = $event;
        }

        return $months;
    }

    /**
     *
     */
    public function getItems(
        array $parameters = null
    ): \Frootbox\Db\Result
    {
        // Obtain model
        $model = $this->db->getRepository($this->itemModel);

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
     *
     */
    public function getItemsVisible(
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
            i.dateEnd >= "' . date('Y-m-d H:i:s') . '" AND
            i.visibility >= ' . (IS_LOGGED_IN ? 1 : 2);

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
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showCategory',
                'categoryId' => $this->getId()
            ])
        ]);
    }
}
