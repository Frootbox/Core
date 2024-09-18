<?php
/**
 *
 */

namespace Frootbox\Persistence;

class Category extends \Frootbox\Persistence\RowModels\ConfigurableNestedSet
{
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Visibility;

    protected $table = 'categories';
    protected $connectionClass = null;
    protected $connectionModel = null;

    /**
     * Insert new child
     */
    public function appendChild(\Frootbox\Db\Row $child): \Frootbox\Db\Row
    {
        if (empty($child->getClassName())) {

            $child->setData([
                'className' => get_class($child)
            ]);
        }

        $child = parent::appendChild($child);


        if (method_exists($child, 'getNewAlias')) {

            if ($child instanceof \Frootbox\Persistence\Alias) {
                d("stop now :-)");
            }

            $child->save();
        }

        return $child;
    }

    /**
     *
     */
    public function connectItem(\Frootbox\Db\Row $item): \Frootbox\Db\Row
    {
        // Obtain connection model
        $connectionModel = ($this->connectionModel === null) ? \Frootbox\Persistence\Repositories\CategoriesConnections::class : $this->connectionModel;
        $model = $this->db->getRepository($connectionModel);

        // Check for existing connections
        $check = $model->fetch([
            'where' => [
                'categoryId' => $this->getId(),
                'categoryClass' => get_class($this),
                'itemId' => $item->getId(),
                'itemClass' => get_class($item)
            ]
        ]);

        if ($check->getCount() > 0) {
            $check->map('delete');
        }

        // Compose new connection
        $connectionData = [
            'pageId' => $this->getPageId(),
            'pluginId' => $this->getPluginId(),
            'categoryId' => $this->getId(),
            'categoryClass' => get_class($this),
            'itemId' => $item->getId(),
            'itemClass' => get_class($item)
        ];

        // Get connection class
        $connectionClass = ($this->connectionClass === null) ? \Frootbox\Db\Row::class : $this->connectionClass;

        // Create new connection
        $row = $model->persist(new $connectionClass($connectionData));

        if ($this->connectionClass !== null) {
            $row->save();
        }

        return $row;
    }

    /**
     * @param \Frootbox\Db\Row $item
     * @return void
     */
    public function disconnectItem(\Frootbox\Db\Row $item): void
    {
        // Obtain connection model
        $model = $this->db->getRepository(\Frootbox\Persistence\Repositories\CategoriesConnections::class);
        $result = $model->fetch([
            'where' => [
                'categoryId' => $this->getId(),
                'categoryClass' => get_class($this),
                'itemId' => $item->getId(),
                'itemClass' => get_class($item),
            ],
        ]);

        $result->map('delete');
    }

    /**
     *
     */

    /**
     *
     */
    public function getChildrenVisible(array $params = null): \Frootbox\Db\Result
    {
        // Fetch visible children
        $children =$this->getChildren([
            'where' => [
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_EDITOR ? 1 : 2)),
            ],
        ]);

        if (!empty($params['hasItem'])) {

            foreach ($children as $index => $child) {

                if (!$child->hasItem($params['hasItem'])) {

                    $children->removeByIndex($index);
                }
            }
        }

        return $children;
    }

    /**
     *
     */
    public function getDirectItems(
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
            categories_2_items x,
            categories c
        WHERE
            x.itemClass = "' . addslashes($model->getClass()) . '" AND
            x.categoryClass = "' . addslashes(get_class($this)) . '" AND
            x.categoryId = c.id AND
            c.lft = ' . $this->getLft() . ' AND
            c.rgt = ' . $this->getRgt() . ' AND
            x.itemId = i.id';

        if (empty($parameters['order'])) {
            $parameters['order'] = 'x.orderId DESC';
        }

        $sql .= ' ORDER BY ' . $parameters['order'];

        $result = $model->fetchByQuery($sql);

        return $result;
    }

    /**
     *
     */
    public function getItems(
        array $parameters = null
    ): \Frootbox\Db\Result
    {
        if (empty($this->itemModel)) {
            throw new \Exception('Item model missing.');
        }

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
            categories_2_items x,
            categories c
        WHERE
            x.itemClass = "' . addslashes($model->getClass()) . '" AND
            x.categoryClass = "' . addslashes(get_class($this)) . '" AND
            x.categoryId = c.id AND
            c.lft >= ' . $this->getLft() . ' AND
            c.rgt <= ' . $this->getRgt() . ' AND
            x.itemId = i.id';

        if (!empty($parameters['order'])) {
            $sql .= ' ORDER BY ' . $parameters['order'];
        }

        $result = $model->fetchByQuery($sql);

        return $result;
    }

    /**
     *
     */
    public function getTitleWithoutFallback($language = null): ?string
    {
        if (empty($language) or $language == DEFAULT_LANGUAGE) {
            return parent::getTitle();
        }

        return $this->getConfig('titles')[$language] ?? null;
    }
    
    /**
     *
     */
    public function hasItem(\Frootbox\Db\Row $item): bool
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
            x.itemId = ' . $item->getId() . '
        LIMIT 1';

        $result = $model->fetchByQuery($sql);

        return $result->getCount() > 0;
    }
}
