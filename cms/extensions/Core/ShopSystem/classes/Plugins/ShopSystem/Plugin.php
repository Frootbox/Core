<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem;

use Frootbox\View\Response;
use Frootbox\View\ResponseJson;

class Plugin extends \Frootbox\Persistence\AbstractPlugin implements \Frootbox\Persistence\Interfaces\Cloneable
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    protected $publicActions = [
        'index',
        'productRequest',
        'showProduct',
        'showCategory'
    ];

    /**
     *
     */
    public function cloneContentFromAncestor(
        \DI\Container $container,
        \Frootbox\Persistence\AbstractRow $ancestor
    ): void
    {
        $idsTable = [
            'products' => [ ],
            'categories' => [
                0 => 0
            ],
            'datasheets' => [ ],
            'datafields' => [ ]
        ];

        // Duplicate categories
        $categoriesRepository = $container->get(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $files = $container->get(\Frootbox\Persistence\Repositories\Files::class);
        $textsRepository = $container->get(\Frootbox\Persistence\Content\Repositories\Texts::class);

        $baseCategory = $categoriesRepository->fetchOne([
            'where' => [
                'uid' => $ancestor->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'id')
            ]
        ]);

        $tree = $categoriesRepository->getTree($baseCategory->getId());

        foreach ($tree as $category) {

            $category->unset([ 'level', 'offspring' ]);

            $newCategory = $category->duplicate();

            $idsTable['categories'][$category->getId()] = $newCategory->getId();

            $newCategory->setPluginId($this->getId());
            $newCategory->setPageId($this->getPage()->getId());
            $newCategory->setUid($this->getUid('categories'));
            $newCategory->setRootId($idsTable['categories'][$category->getRootId()]);
            $newCategory->setParentId($idsTable['categories'][$category->getParentId()]);
            $newCategory->setAlias(null);
            $newCategory->save();

            // Duplicate categorys images files
            $uid = $category->getUid('image');

            $result = $files->fetch([
                'where' => [
                    'uid' => $uid
                ]
            ]);

            foreach ($result as $file) {

                $newFile = $file->duplicate();
                $newFile->setUid($newCategory->getUid('image'));
                $newFile->save();
            }
        }


        // Duplicate datasheets
        $datasheetsRepository = $container->get(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets::class);

        // Fetch datasheets
        $result = $datasheetsRepository->fetch([
            'where' => [ 'pluginId' => $ancestor->getId() ],
            'order' => [ 'title' ]
        ]);

        foreach ($result as $datasheet) {

            $newDatasheet = $datasheet->duplicate();

            $newDatasheet->setPluginId($this->getId());
            $newDatasheet->setPageId($this->getPage()->getId());
            $newDatasheet->save();

            $idsTable['datasheets'][$datasheet->getId()] = $newDatasheet->getId();

            $fields = $container->call([ $datasheet, 'getFields' ]);

            foreach ($fields as $field) {

                $newField = $field->duplicate();
                $newField->setPluginId($this->getId());
                $newField->setPageId($this->getPage()->getId());
                $newField->setParentId($newDatasheet->getId());
                $newField->save();

                $idsTable['datafields'][$field->getId()] = $newField->getId();
            }
        }

        // Duplicate products
        $productsRepository = $container->get(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);
        $dataRepository = $container->get(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ProductsData::class);

        // Fetch products
        $products = $productsRepository->fetch([
            'where' => [ 'pluginId' => $ancestor->getId() ],
            'order' => [ 'title' ]
        ]);

        foreach ($products as $product) {

            if (empty($idsTable['datasheets'][$product->getDatasheetId()])) {
                continue;
            }

            $newProduct = $product->duplicate();
            $newProduct->setPluginId($this->getId());
            $newProduct->setPageId($this->getPage()->getId());
            $newProduct->setDatasheetId($idsTable['datasheets'][$product->getDatasheetId()]);
            $newProduct->setAlias(null);

            $newProduct->save();

            $idsTable['products'][$product->getId()] = $newProduct->getId();

            // Duplicate products data
            $dataFields = $dataRepository->fetch([
                'where' => [
                    'productId' => $product->getId()
                ]
            ]);

            foreach ($dataFields as $data) {

                $newData = $data->duplicate();
                $newData->setProductId($newProduct->getId());
                $newData->setFieldId($idsTable['datafields'][$data->getFieldId()]);
                $newData->save();
            }

            // Duplicate products images
            $uid = $product->getUid('images');

            $result = $files->fetch([
                'where' => [
                    'uid' => $uid
                ]
            ]);

            $newUid = $newProduct->getUid('images');

            foreach ($result as $file) {

                $newFile = $file->duplicate();
                $newFile->setUid($newUid);
                $newFile->save();
            }

            // Duplicate products files
            $uid = $product->getUid('files');

            $result = $files->fetch([
                'where' => [
                    'uid' => $uid
                ]
            ]);

            $newUid = $newProduct->getUid('files');

            foreach ($result as $file) {

                $newFile = $file->duplicate();
                $newFile->setUid($newUid);
                $newFile->save();
            }


            // Duplicate products files
            $uid = $product->getUid('image');

            $result = $files->fetch([
                'where' => [
                    'uid' => $uid
                ]
            ]);

            $newUid = $newProduct->getUid('image');

            foreach ($result as $file) {

                $newFile = $file->duplicate();
                $newFile->setUid($newUid);
                $newFile->save();
            }

            // Duplicate texts
            $texts = $textsRepository->fetch([
                'where' => [
                    new \Frootbox\Db\Conditions\Like('uid', $product->getUidBase() . '%')
                ]
            ]);

            foreach ($texts as $text) {

                $newText = $text->duplicate();

                $extract = $newText::extractUid($text->getDataRaw('uid'));

                $newText->setUid($newProduct->getUid($extract['segment']));
                $newText->save();
            }
        }

        // Add products to categories
        $tree = $categoriesRepository->getTree($baseCategory->getId());

        foreach ($tree as $category) {

            $products = $container->call([ $category, 'getProducts' ]);

            foreach ($products as $product) {

                if (empty($idsTable['products'][$product->getId()])) {
                    continue;
                }

                $newCat = $categoriesRepository->fetchById($idsTable['categories'][$category->getId()]);
                $newProduct = $productsRepository->fetchById($idsTable['products'][$product->getId()]);

                $newCat->connectItem($newProduct);
            }
        }
    }

    /**
     *
     */
    public function getCategory(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): ?\Frootbox\Ext\Core\ShopSystem\Persistence\Category
    {
        if (!$this->hasAttribute('categoryId')) {
            return null;
        }

        $category = $categoriesRepository->fetchById($this->getAttribute('categoryId'));

        return $category;
    }

    /**
     * Get plugins root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getProducts(
        array $parameters = null,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): \Frootbox\Db\Result
    {

        $params = [
            'where' => [
                'pluginId' => $this->getId(),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility', (IS_LOGGED_IN ? 1 : 2)),
            ],
        ];

        if (!empty($parameters['order'])) {
            $params['order'] = [ $parameters['order'] ];
        }

        $result = $productsRepository->fetch($params);



        return $result;
    }

    /**
     *
     */
    public function getProductsByTags(
        $tag,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): \Frootbox\Db\Result
    {
        $sql = 'SELECT 
            p.*
        FROM
            tags t,
            shop_products p
        WHERE
            t.tag = :tag AND
            t.itemClass = "Frootbox\\\\Ext\\\\Core\\\\ShopSystem\\\\Persistence\\\\Product" AND
            t.itemId = p.id
        ';

        $result = $productsRepository->fetchByQuery($sql, [ 'tag' => $tag ]);

        return $result;
    }

    /**
     *
     */
    public function getRootCategory(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
    ): ?\Frootbox\Ext\Core\ShopSystem\Persistence\Category
    {
        $category = $categoriesRepository->fetchOne([
            'where' => [
                new \Frootbox\Db\Conditions\MatchColumn('id', 'rootId'),
            ],
        ]);

        return $category;
    }

    /**
     *
     */
    public function getTopCategories(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): \Frootbox\Db\Result
    {
        // Fetch categories
        $categories = $categoriesRepository->fetch([
            'where' => [
                'uid' => $this->getUid('categories'),
                new \Frootbox\Db\Conditions\MatchColumn('rootId', 'parentId'),
                new \Frootbox\Db\Conditions\GreaterOrEqual('visibility',(IS_LOGGED_IN ? 1 : 2)),
            ],
            'order' => [ 'lft ASC' ]
        ]);

        return $categories;
    }

    /**
     *
     */
    public function ajaxGetOptionsInStockAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
    ): Response
    {
        // Fetch option
        $option = $optionRepository->fetchById($get->get('optionId'));

        $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' . $option->getProductId() . ' AND JSON_CONTAINS(groupData, \'{"' . $option->getGroupId() . '":"' . $option->getId() . '"}\');';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetchAll();
        $stocks = [];

        foreach ($result as $stock) {

            $groupData = json_decode($stock['groupData'], true);

            foreach ($groupData as $optionId) {

                if (!isset($stocks[$optionId])) {
                    $stocks[$optionId] = 0;
                }

                $stocks[$optionId] += $stock['amount'];
            }
        }

        // Fetch selected option
        parse_str($get->get('xdata'), $xdata);
        ksort($xdata['options']);

        $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' . $option->getProductId() . ' AND JSON_CONTAINS(groupData, \'' . json_encode($xdata['options']) . '\');';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();

        $payload = [
            'stocks' => $stocks,
        ];

        if (!empty($result)) {
            $payload['selected'] = [
                'price' => $result['price'] / 100,
                'amount' => $result['amount'],
            ];
        }

        return new ResponseJson($payload);
    }

    /**
     *
     */
    public function ajaxSubmitRequestAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        die("FORBIDDEN");
        // Fetch product
        $product = $productsRepository->fetchById($this->getAttribute('productId'));

        $view->set('product', $product);

        // Build mail source
        preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

        $view->set('baseVendor', $match[1]);
        $view->set('baseExtension', $match[2]);

        $view->set('post', $post);

        $file = $this->getPath() . 'resources/private/builder/MailRequest.html.twig';
        $source = $view->render($file);

        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject('Produkt-Anfrage: ' . $product->getTitle());
        $mail->setBodyHtml($source);
        $mail->setReplyTo($post->get('email'));

        $mail->clearTo();
        $mail->addTo($config->get('mail.defaults.from.address'));
        $mailTransport->send($mail);


        return new ResponseJson([

        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return new Response;
    }

    /**
     *
     */
    public function productRequestAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($this->getAttribute('productId'));

        return new Response([
            'product' => $product
        ]);
    }

    /**
     *
     */
    public function showCategoryAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository
    ): Response
    {
        // Fetch category
        $category = $categoriesRepository->fetchById($this->getAttribute('categoryId'));

        if (!empty($category->getConfig('layoutId'))) {
            $this->setOverrideTemplate($category->getConfig('layoutId'));
        }

        return new Response([
            'get' => $get,
            'category' => $category
        ]);
    }

    /**
     *
     */
    public function showManufacturerAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Fetch manufacturer
        $manufacturer = $manufacturersRepository->fetchById($this->getAttribute('manufacturerId'));

        return new Response([
            'manufacturer' => $manufacturer
        ]);
    }

    /**
     *
     */
    public function showProductAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Datasheets $datasheetsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories $categoriesRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\CategoriesConnections $categoriesConnectionsRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($this->getAttribute('productId'));

        if (empty($product->getVisibility())) {
            throw new \Frootbox\Exceptions\AccessDenied('ProductCurrentlyNotAvailable');
        }

        // Fetch datasheet
        $datasheet = $datasheetsRepository->fetchById($product->getDatasheetId());

        if (!empty($templateId = $datasheet->getConfig('products.templateId'))) {
            $this->setOverrideTemplate('ShowProduct' . str_pad($templateId, 2, '0', STR_PAD_LEFT));
        }

        // Fetch category
        if($this->hasAttribute('categoryId')) {

            $category = $categoriesRepository->fetchById($this->getAttribute('categoryId'));

            $conenction = $categoriesConnectionsRepository->fetchOne([
                'where' => [
                    'categoryId' => $category->getId(),
                    'itemId' => $product->getId(),
                    'itemClass' => \Frootbox\Ext\Core\ShopSystem\Persistence\Product::class,
                ],
            ]);

            $product->setAliases(json_encode([ 'index' => $conenction->getLanguageAliases() ]));
        }

        return new Response([
            'product' => $product,
            'datasheet' => $datasheet,
            'category' => $category ?? null,
        ]);
    }
}
