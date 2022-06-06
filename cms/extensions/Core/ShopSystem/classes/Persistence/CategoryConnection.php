<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Persistence;

class CategoryConnection extends \Frootbox\Persistence\CategoryConnection implements \Frootbox\Persistence\Interfaces\MultipleAliases
{
    use \Frootbox\Persistence\Traits\Alias;

    protected $model = Repositories\CategoriesConnections::class;

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
     * {@inheritdoc}
     */
    protected function getNewAlias(): ?\Frootbox\Persistence\Alias
    {
        // Fetch category
        $categoriesRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $category = $categoriesRepository->fetchById($this->getCategoryId());

        // Fetch product
        $productsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);
        $product = $productsRepository->fetchById($this->getItemId());

        // Build virtual directory
        $trace = $category->getTrace();
        $trace->shift();

        $virtualDirectory = [ ];

        foreach ($trace as $child) {
            $virtualDirectory[] = $child->getTitle();
        }

        $virtualDirectory[] = $product->getTitle();

        return new \Frootbox\Persistence\Alias([
            'pageId' => $product->getPageId(),
            'virtualDirectory' => $virtualDirectory,
            'visibility' => 2,
            'payload' => $this->generateAliasPayload([
                'action' => 'showProduct',
                'productId' => $product->getId(),
                'categoryId' => $category->getId(),
            ]),
        ]);
    }

    /**
     * @return array
     */
    public function getLanguageAliases(): array
    {
        $aliases = json_decode($this->data['aliases'], true);

        return $aliases['index'] ?? [];
    }

    /**
     * @return array
     */
    public function getNewAliases(): array
    {
        // Fetch category
        $categoriesRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Categories::class);
        $category = $categoriesRepository->fetchById($this->getCategoryId());

        // Fetch product
        $productsRepository = $this->db->getRepository(\Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products::class);
        $product = $productsRepository->fetchById($this->getItemId());

        if (!empty($product->getConfig('titles'))) {

            $list = [ 'index' => [] ];

            foreach ($product->getConfig('titles') as $language => $title) {

                if (empty($title)) {
                    continue;
                }

                // Build virtual directory
                $trace = $category->getTrace();

                $trace->shift();

                $virtualDirectory = [ ];

                foreach ($trace as $child) {
                    $virtualDirectory[] = $child->getTitle($language);
                }

                $virtualDirectory[] = $title;

                $list['index'][] = new \Frootbox\Persistence\Alias([
                    'language' => $language,
                    'pageId' => $product->getPageId(),
                    'virtualDirectory' => $virtualDirectory,
                    'visibility' => 2,
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showProduct',
                        'productId' => $product->getId(),
                        'categoryId' => $category->getId(),
                    ]),
                ]);
            }


            return $list;
        }
        else {

            // Build virtual directory
            $trace = $category->getTrace();
            $trace->shift();

            $virtualDirectory = [ ];

            foreach ($trace as $child) {
                $virtualDirectory[] = $child->getTitle();
            }

            $virtualDirectory[] = $product->getTitle();

            return [
                'index' => [ new \Frootbox\Persistence\Alias([
                    'pageId' => $product->getPageId(),
                    'virtualDirectory' => $virtualDirectory,
                    'visibility' => 2,
                    'payload' => $this->generateAliasPayload([
                        'action' => 'showProduct',
                        'productId' => $product->getId(),
                        'categoryId' => $category->getId(),
                    ]),
                ]) ],
            ];
        }
    }
}