<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Category extends \Frootbox\Persistence\Category implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias {
        getUri as getTraitUri;
    }
    protected $model = Repositories\Categories::class;
    protected $connectionModel = Repositories\CategoriesConnections::class;
    protected $connectionClass = CategoryConnection::class;
    protected $itemModel = Repositories\Products::class;

    /**
     *
     */
    public function getAlias($section = 'index', $language = null): ?string
    {
        $aliases = !empty($this->data['aliases']) ? json_decode($this->data['aliases'], true) : [];

        if ($language === null) {
            $language = GLOBAL_LANGUAGE;
        }

        if (!empty($aliases[$section][$language])) {
            return $aliases[$section][$language];
        }

        if (!empty($aliases[$section][DEFAULT_LANGUAGE])) {
            $alias = $aliases[$section][DEFAULT_LANGUAGE];
        }
        else {
            $alias = $this->data['alias'] ?? null;
        }

        if (MULTI_LANGUAGE and GLOBAL_LANGUAGE != DEFAULT_LANGUAGE) {
            $alias .= '?forceLanguage=' . GLOBAL_LANGUAGE;
        }

        return $alias;
    }

    /**
     *
     */
    public function getFilters(): array
    {
        if (empty($this->getConfig('filters'))) {
            return [];
        }

        $fieldsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        $filters = [];

        foreach ($this->getConfig('filters') as $index => $fieldId) {

            $field = $fieldsRepository->fetchById($fieldId);

            $filters[] = [
                'field' => $field
            ];
        }

        return $filters;
    }

    /**
     *
     */
    public function getItems(
        array $parameters = null
    ): \Frootbox\Db\Result
    {
        if (empty($parameters['filter'])) {
            $parameters['filter'] = [];
        }

        // Obtain model
        $model = $this->db->getRepository($this->itemModel);

        // Build sql
        $sql = 'SELECT
            i.*,
            MIN(x.config) as connConfig,
            MIN(x.id) as connId,
            MIN(x.alias) as alias,
            MIN(x.aliases) as aliases
        FROM
            ' . $model->getTable() . ' i,
            categories c,
            categories_2_items x';

        foreach ($parameters['filter'] as $index => $filter) {
            $sql .= ', shop_products_data d' . $index . ' ';
        }

        $sql .= ' WHERE ';
        $params = [];

        foreach ($parameters['filter'] as $index => $filter) {
            $sql .= '
                d' . $index . '.productId = i.id AND
                d' . $index . '.fieldId = ' . $filter['id'] . ' AND
                d' . $index . '.valueText = :valueText' . $index . ' AND ';

            $params['valueText' . $index] = $filter['key'];
        }

        if (!empty($parameters['noInheritance'])) {

            $sql .= '
                c.id = ' . $this->getId() . ' AND
            ';

        }
        else {

            $sql .= '            
                c.lft >= ' . $this->getLft() . ' AND
                c.rgt <= ' . $this->getRgt() . ' AND
            ';
        }

        $sql .= ' c.rootId = ' . $this->getRootId() . ' AND 
            i.visibility >= ' . (IS_LOGGED_IN ? '1' : '2') . ' AND
            x.categoryId = c.id AND            
            x.itemId = i.id
        GROUP BY
            x.itemId,
            x.orderId ';

        if (empty($parameters['order'])) {
            $parameters['order'] = ' orderId DESC ';
        }

        $sql .= ' ORDER BY ' . $parameters['order'];

        $result = $model->fetchByQuery($sql, $params);

        return $result;
    }

    /**
     *
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'] ?? [];
    }

    /**
     * Generate categories alias
     *
     * @return \Frootbox\Persistence\Alias|null
     */
    protected function getNewAlias ( ): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noCategoriesDetailPages'))) {
            return null;
        }

        $trace = $this->getTrace();
        $trace->shift();

        $vd = [ ];

        foreach ($trace as $child) {
            $vd[] = $child->getTitle();
        }

        if ($this->getParentId() == 0) {
            $vd[] = 'kategorien';
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
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noCategoriesDetailPages'))) {
            return [];
        }

        $trace = $this->getTrace();
        $trace->shift();

        $vd = [ ];

        foreach ($trace as $child) {
            $vd[] = $child->getTitle();
        }

        if ($this->getParentId() == 0) {
            $vd[] = 'kategorien';
        }

        if (!empty($this->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($this->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $this->getPageId(),
                    'virtualDirectory' => $vd,
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showCategory',
                        'categoryId' => $this->getId()
                    ]),
                ]);
            }

            return $list;
        }
        else {

            return [
                'index' => [ new \Frootbox\Persistence\Alias([
                    'pageId' => $this->getPageId(),
                    'language' => $this->getLanguage() ?? GLOBAL_LANGUAGE,
                    'virtualDirectory' => $vd,
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showCategory',
                        'categoryId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
    }

    /**
     *
     */
    public function getProducts(
        array $options = null,
    ): \Frootbox\Db\Result
    {
        $productsRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);

        // Build sql
        $sql = 'SELECT
            p.*,
            i.config as connConfig,
            i.id as connId,
            i.alias,
            i.aliases,
            i.categoryId
        FROM
            shop_products p,
            categories_2_items i
        WHERE
        ';

        if (empty($options['ignoreVisibility'])) {
            $sql .= ' p.visibility >= ' . (IS_LOGGED_IN ? '1' : '2') . ' AND ';
        }

        $sql .= '
            i.categoryId = ' . $this->getId() . ' AND
            i.categoryClass = :categoryClass AND 
            i.itemId = p.id AND
            i.itemClass = :itemClass
        ORDER BY
            i.orderId DESC';

        $result = $productsRepository->fetchByQuery($sql, [
            ':categoryClass' => \Frootbox\Ext\Core\ShopSystem\Persistence\Category::class,
            ':itemClass' => \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class,
        ]);

        return $result;
    }

    /**
     *
     */
    public function getTitle($language = null): ?string
    {
        if (!MULTI_LANGUAGE) {
            return parent::getTitle();
        }

        if ($language === null) {
            $language = GLOBAL_LANGUAGE;
        }

        return (!empty($this->getConfig('titles')[$language]) ? $this->getConfig('titles')[$language] : parent::getTitle());
    }

    /**
     *
     */
    public function getUri(array $options = null): string
    {
        if (!empty($this->getConfig('targetCategoryId'))) {

            $repository = $this->getRepository();
            $target = $repository->fetchById($this->getConfig('targetCategoryId'));

            return $target->getUri();
        }

        if (!empty($this->getConfig('url'))) {
            return $this->getConfig('url');
        }

        return $this->getTraitUri($options);
    }
}
