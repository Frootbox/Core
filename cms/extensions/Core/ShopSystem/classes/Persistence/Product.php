<?php
/**
 * @author Jan Habbo Brüning <jan.habbo.bruening@gmail.com>
 *
 * @noinspection PhpUnnecessaryLocalVariableInspection
 * @noinspection SqlNoDataSourceInspection
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class Product extends \Frootbox\Persistence\AbstractConfigurableRow implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias;
    use \Frootbox\Persistence\Traits\Uid;
    use \Frootbox\Persistence\Traits\Tags;
    use \Frootbox\Persistence\Traits\Visibility;

    protected $model = Repositories\Products::class;
    protected $table = 'shop_products';

    /**
     *
     */
    public function delete()
    {
        // Cleanup data fields
        $model = $this->db->getModel(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData::class);
        $result = $model->fetch([
            'where' => [
                'productId' => $this->getId()
            ]
        ]);

        $result->map('delete');

        // Cleanup category connections
        $model = $this->db->getModel(\Frootbox\Persistence\Repositories\CategoriesConnections::class);
        $result = $model->fetch([
            'where' => [
                'itemId' => $this->getId(),
                'itemClass' => get_class($this)
            ]
        ]);

        $result->map('delete');

        parent::delete();
    }

    /**
     *
     */
    public function equipmentAdd(Product $equipment): void
    {
        $list = $this->getConfig('equipment') ?? [];

        $list[] = [
            'amount' => 1,
            'productId' => $equipment->getId()
        ];

        $this->addConfig([
            'equipment' => $list
        ]);

        $this->save();
    }

    /**
     *
     */
    public function equipmentRemove(Product $equipment): void
    {
        $list = $this->getConfig('equipment') ?? [];

        foreach ($list as $index => $equipmentData) {

            if ($equipmentData['productId'] == $equipment->getId()) {
                unset($list[$index]);
                break;
            }
        }

        // Reset array keys
        $list = array_values($list);

        $this->unsetConfig('equipment');
        $this->addConfig([
            'equipment' => $list
        ]);

        $this->save();
    }

    /**
     *
     */
    public function equipmentUpdate(array $equipment): void
    {
        unset($equipment['product']);

        $list = $this->getConfig('equipment') ?? [];

        foreach ($list as $index => $equipmentData) {

            if ($equipment['productId'] == $equipmentData['productId']) {
                $list[$index] = $equipment;
                break;
            }
        }

        $this->unsetConfig('equipment');
        $this->addConfig([
            'equipment' => $list
        ]);
    }

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
     * @return \Frootbox\Db\Result
     */
    public function getCategories(): \Frootbox\Db\Result
    {
        // Build sql
        $sql = 'SELECT
            c.*
        FROM
            categories c,
            categories_2_items x
        WHERE
            x.categoryId = c.id AND
            x.categoryClass = :categoryClass AND
            x.itemClass = :itemClass AND
            x.itemId = '  .$this->getId();

        // Fetch categories
        $categoryRepository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $categories = $categoryRepository->fetchByQuery($sql, [
            ':categoryClass' => \Frootbox\Ext\Core\ShopSystem\Persistence\Category::class,
            ':itemClass' => \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class,
        ]);

        return $categories;
    }

    /**
     *
     */
    public function getDatasheet(): \Frootbox\Ext\Core\ShopSystem\Persistence\Datasheet
    {
        // Fetch datasheet
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets::class);
        $datasheet = $repository->fetchById($this->getDatasheetId());

        return $datasheet;
    }

    /**
     *
     */
    public function getEquipment(array $params = null)
    {
        $list = $this->getConfig('equipment') ?? [];

        $repository = $this->getRepository();

        $vis = IS_LOGGED_IN ? 1 : 2;

        foreach ($list as $index => $equipment) {

            try {

                $product = $repository->fetchById($equipment['productId']);

                if (empty($params['shopPlugin']->config['ignoreEquipmentsVisibility']) and $product->getVisibility() < $vis) {
                    unset($list[$index]);
                    continue;
                }

                $list[$index]['product'] = $product;
            }
            catch ( \Exception $e ) {
                unset($list[$index]);
            }
        }

        return $list;
    }

    /**
     *
     */
    public function getEquipmentById($equipmentId): ?array
    {
        $list = $this->getConfig('equipment') ?? [];

        $repository = $this->getRepository();

        $vis = IS_LOGGED_IN ? 1 : 2;

        foreach ($list as $index => $equipment) {

            if ($equipmentId != $equipment['productId']) {
                continue;
            }

            try {

                $product = $repository->fetchById($equipment['productId']);

                if (empty($params['shopPlugin']->config['ignoreEquipmentsVisibility']) and $product->getVisibility() < $vis) {
                    unset($list[$index]);
                    continue;
                }

                $list[$index]['orgId'] = $this->getId();
                $list[$index]['product'] = $product;

                return $list[$index];
            }
            catch ( \Exception $e ) {

                return null;
            }
        }

        return $list;
    }



    /**
     *
     */
    public function getField(int $fieldId): ?\Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetField
    {
        $model = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        // Build sql
        $sql = 'SELECT
            a.id as fieldId,
            a.*,
            d.valueText as valueDisplay
        FROM
            assets a        
        LEFT JOIN
            shop_products_data d
        ON
            d.productId = ' . $this->getId() . ' AND
            d.fieldId = a.id
        WHERE
            a.className = "Frootbox\\\\Ext\\\\Core\\\\ShopSystem\\\\Persistence\\\\DatasheetField" AND
            a.parentId = ' . $this->getDatasheetId() . ' AND
            a.id = ' . $fieldId . '
        LIMIT 1';

        $result = $model->fetchByQuery($sql);

        return $result->current();
    }

    /**
     * @param string $name
     * @return DatasheetField|null
     */
    public function getFieldByName(string $name): ?\Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetField
    {
        $model = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        // Build sql
        $sql = 'SELECT
            a.id as fieldId,
            a.*,
            d.valueText as valueText,
            d.valueInt as valueInt
        FROM
            assets a        
        LEFT JOIN
            shop_products_data d
        ON
            d.productId = ' . $this->getId() . ' AND
            d.fieldId = a.id
        WHERE
            a.className = :className AND
            a.parentId = ' . $this->getDatasheetId() . ' AND
            a.title = :name
        LIMIT 1';

        $result = $model->fetchByQuery($sql, [
            ':className' => \Frootbox\Ext\Core\ShopSystem\Persistence\DatasheetField::class,
            ':name' => $name
        ]);

        return $result->current();
    }

    /**
     * Get products fields
     */
    public function getFields(string $section = null, array $options = null): \Frootbox\Db\Result
    {
        $model = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetFields::class);

        // Build sql
        $sql = 'SELECT
            a.id as fieldId,
            a.*,
            d.id as dataId,
            d.valueText,
            d.valueInt,
            d.type
        FROM
            assets a        
        LEFT JOIN
            shop_products_data d
        ON
            d.productId = ' . $this->getId() . ' AND
            d.fieldId = a.id
        WHERE
            a.className = "Frootbox\\\\Ext\\\\Core\\\\ShopSystem\\\\Persistence\\\\DatasheetField" AND
            a.parentId = ' . $this->getDatasheetId() . '
        ORDER BY
            a.orderID DESC';

        $result = $model->fetchByQuery($sql);

        if ($section !== null) {

            foreach ($result as $index => $field) {

                if ($field->getValueDisplay() === null and empty($options['forceEmpty'])) {
                    $result->removeByIndex($index);
                    continue;
                }

                if ($section === null OR $section == 'default') {

                    if (!empty($field->getConfig('section')) and $field->getConfig('section') != 'default') {
                        $result->removeByIndex($index);
                    }
                }
                else {

                    if ($field->getConfig('section') != $section) {
                        $result->removeByIndex($index);
                    }
                }
            }
        }
        else {

            foreach ($result as $index => $field) {


                if (empty($field->getValueDisplay()) and empty($options['forceEmpty'])) {
                    $result->removeByIndex($index);
                }
            }
        }

        return $result;
    }

    /**
     * Get products fields
     */
    public function getFieldsBySection(array $options = null): array
    {
        $list = [];

        foreach ($this->getFields(null, $options) as $field) {

            $section = $field->getSection() ?? 'default';

            $list[$section][] = $field;
        }

        return $list;
    }

    /**
     * @return \Frootbox\Db\Result
     */
    public function getFiles(): \Frootbox\Db\Result
    {
        // Fetch files
        $filesRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);
        $result = $filesRepository->fetchResultByUid($this->getUid('files'), [
            'order' => 'orderId DESC',
        ]);

        return $result;
    }

    /**
     *
     */
    public function getImages(array $params = null): \Frootbox\Db\Result
    {
        // Fetch files
        $filesRepository = $this->db->getRepository(\Frootbox\Persistence\Repositories\Files::class);
        $result = $filesRepository->fetchResultByUid($this->getUid('images'), [
            'order' => 'orderId DESC',
        ]);

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
     *
     */
    public function getManufacturer(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\Manufacturer
    {
        if (empty($this->getManufacturerId())) {
            return null;
        }

        $repository =  $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers::class);

        return $repository->fetchById($this->getManufacturerId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        if (!empty($this->getConfig('noProductsDetailPages'))) {
            return null;
        }

        return new \Frootbox\Persistence\Alias([
            'pageId' => $this->getPageId(),
            'virtualDirectory' => [
                $this->getTitle()
            ],
            'uid' => $this->getUid('alias'),
            'payload' => $this->generateAliasPayload([
                'action' => 'showProduct',
                'productId' => $this->getId()
            ])
        ]);
    }

    /**
     * Generate page alias
     */
    public function getNewAliases(): array
    {
        if (!empty($this->getConfig('noProductsDetailPages'))) {
            return [];
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
                    'virtualDirectory' => [
                        $title,
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showProduct',
                        'productId' => $this->getId()
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
                    'virtualDirectory' => [
                        $this->getTitle()
                    ],
                    'uid' => $this->getUid('alias'),
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showProduct',
                        'productId' => $this->getId()
                    ]),
                ]) ],
            ];
        }
    }

    /**
     * @return ProductNutrition
     * @throws \Frootbox\Exceptions\RuntimeError
     */
    public function getNutritionTable(): \Frootbox\Ext\Core\ShopSystem\Persistence\ProductNutrition
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsNutrition::class);

        $nutrition = $repository->fetchOne([
            'where' => [
                'productId' => $this->getId(),
            ],
        ]);

        if ($nutrition === null) {

            $nutrition = new \Frootbox\Ext\Core\ShopSystem\Persistence\ProductNutrition([
                'productId' => $this->getId(),
            ]);

            $repository->persist($nutrition);
        }

        return $nutrition;
    }

    /**
     *
     */
    public function getOptionalFields(): ?array
    {
        $list = [];

        foreach ($this->getFields() as $field) {

            if ($field->isOptional()) {
                $list[] = $field;
            }
        }

        return $list;
    }

    /**
     *
     */
    public function getPackagingSize()
    {
        return str_replace(',', '.', $this->data['packagingSize'] / 1000);
    }

    /**
     *
     */
    public function getPriceForVariant(Variant $variant): float
    {
        return $this->getPriceGross() + $variant->getPrice();
    }

    /**
     *
     */
    public function getPriceGross(): float
    {
        return (float) round($this->getPrice() * (1 + $this->getTaxrate() / 100), 2);
    }

    /**
     *
     */
    public function getPriceGrossForUnit($packagingSize, $unit): float
    {
        return $this->getPriceGross() / $this->getPackagingSize() * $packagingSize;
    }

    /**
     *
     */
    public function getPriceOld(): ?float
    {
        if (empty($this->config['priceOld'])) {
            return null;
        }

        return (float) $this->config['priceOld'];
    }

    /**
     *
     */
    public function getRecommendations()
    {
        $list = $this->getConfig('recommendations') ?? [];

        $repository = $this->getRepository();

        foreach ($list as $index => $equipment) {

            try {

                // Fetch product
                $product = $repository->fetchById($equipment['productId']);

                if ($product->getVisibility() < 2) {

                    unset($list[$index]);

                    continue;
                }

                $list[$index]['product'] = $product;
            }
            catch ( \Exception $e ) {
                unset($list[$index] );
            }
        }

        return $list;
    }

    /**
     *
     */
    public function getShippingCosts(): ?\Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts
    {
        if (empty($this->getShippingId())) {
            return null;
        }

        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts::class);
        $shippingCosts = $repository->fetchById($this->getShippingId());

        return $shippingCosts;
    }

    /**
     *
     */
    public function getStocks(array $options): ?\Frootbox\Ext\Core\ShopSystem\Persistence\Stock
    {
        ksort($options);

        $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' . $this->getId() . ' AND JSON_CONTAINS(groupData, \'' . json_encode($options) . '\') LIMIT 1';
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock::class);

        $check = $repository->fetchByQuery($sql);

        if ($check->getCount() == 0) {
            return null;
        }

        return $check->current();
    }

    /**
     *
     */
    public function getStocksFlat(): array
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock::class);
        $result = $repository->fetch([
            'where' => [
                'productId' => $this->getId(),
            ],
        ]);

        $price = $this->getPriceGross();

        $list = [];
        $loop = 0;

        foreach ($result as $stock) {

            if (empty($stock->getAmount())) {
                continue;
            }

            $string = $stock->getOptionsAsString();
            $key =  $string . str_pad(++$loop, 4, '0', STR_PAD_LEFT);

            $stock->setFlat($string);

            if (empty($stock->getPrice())) {
                $stock->setPrice($price * 100);
            }

            $list[$key] = $stock;
        }

        ksort($list);

        return $list;

    }


    /**
     *
     */
    public function getStocksForOption(\Frootbox\Ext\Core\ShopSystem\Persistence\Option $option): int
    {
        $stocksRepository = $this->getDb();

        // $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' .$this->getId() . ' AND JSON_EXTRACT(groupData, "' .  . '") = ' . $option->getId() . ';';

        $sql = 'SELECT SUM(amount) as amount FROM `shop_products_stocks` WHERE productId = ' .$this->getId() . ' AND JSON_CONTAINS(groupData, \'{"' . $option->getGroupId() . '":"' . $option->getId() . '"}\');';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['amount'] ?? 0;
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
    public function getVariants(): \Frootbox\Db\Result
    {
        $model = $this->db->getModel(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants::class);
        $result = $model->fetch([
            'where' => [
                'parentId' => $this->getId()
            ]
        ]);

        return $result;
    }

    /**
     *
     */
    public function hasEquipment(int $equipmentId): bool
    {
        if (empty($this->config['equipment'])) {
            return false;
        }

        foreach ($this->config['equipment'] as $equipment) {

            if ($equipment['productId'] == $equipmentId) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function hasOptions(): bool
    {
        $repository = $this->getDb()->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option::class);
        $check = $repository->fetchOne([
            'where' => [
                'productId' => $this->getId(),
            ],
        ]);

        return !empty($check);
    }

    /**
     *
     */
    public function recommendationAdd(Product $recommendation): void
    {
        $list = $this->getConfig('recommendations') ?? [];

        $list[] = [
            'amount' => 1,
            'productId' => $recommendation->getId()
        ];

        $this->addConfig([
            'recommendations' => $list
        ]);

        $this->save();
    }


    /**
     *
     */
    public function recommendationRemove(Product $recommendation): void
    {
        $list = $this->getConfig('recommendations') ?? [];

        foreach ($list as $index => $equipmentData) {

            if ($equipmentData['productId'] == $recommendation->getId()) {
                unset($list[$index]);
                break;
            }
        }

        // Reset array keys
        $list = array_values($list);

        $this->unsetConfig('recommendations');
        $this->addConfig([
            'recommendations' => $list
        ]);

        $this->save();
    }


    /**
     *
     */
    public function setPackagingSize($size): void
    {
        $this->data['packagingSize'] = (float) $size * 1000;

        $this->changed['packagingSize'] = true;
    }
}
