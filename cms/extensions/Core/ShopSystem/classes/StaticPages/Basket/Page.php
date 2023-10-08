<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\StaticPages\Basket;

use Frootbox\View\Response;

class Page extends \Frootbox\AbstractStaticPage
{
    /**
     *
     */
    public function addProduct(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $configuration,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
        \DI\Container $container,
    ): Response
    {
        // Clear basket if requested
        if (!empty($get->get('clearAll'))) {
            $shopcart->clearItems();
        }

        if (!empty($post->get('personal'))) {
            $shopcart->setPersonal($post->get('personal'));
        }

        // Obtain product ID
        $productId = $get->get('productId') ?? $post->get('productId');

        // Fetch product
        $product = $productsRepository->fetchById($productId);

        // Generate item key
        $key = $product->getId();

        if (!empty($variantId = $post->get('variant')) and $variantId != 'default') {
            $key .= '-' . $variantId;
        }

        $key .= '-' . json_encode($post->get('options'));
        $key = substr(md5($key), 0, 7);

        if ($product->hasOptions()) {
            $key .= '-' . rand(1000, 9999);
        }

        $options = [];

        $forcePriceGross = null;
        $forcePrice = null;
        $selectedOptions = $post->get('options');

        if (!empty($post->get('stockId'))) {

            $stock = $stockRepository->fetchById($post->get('stockId'));

            $selectedOptions = json_decode($stock->getGroupData(), true);
        }

        if (!empty($selectedOptions)) {

            $optCheck = [];
            $surcharge = 0;

            foreach ($selectedOptions as $groupId => $optionId) {

                // Fetch option
                $option = $optionRepository->fetchById($optionId);

                $options[] = [
                    'groupId' => $option->getGroupId(),
                    'group' => $option->getGroup()->getTitle(),
                    'optionId' => $option->getId(),
                    'option' => $option->getTitle(),
                    'surcharge' => $option->getSurcharge(),
                ];

                $optCheck[$option->getGroupId()] = $option->getId();

                if (!empty($option->getSurcharge())) {
                    $surcharge += $option->getSurcharge();
                }
            }

            ksort($optCheck);

            $stock = $product->getStocks($optCheck);

            if (!empty($stock->getPrice())) {
                $forcePriceGross = $stock->getPrice();
                $tax = $forcePriceGross / (1 + $product->getTaxrate() / 100) * ($product->getTaxrate() / 100);
                $forcePrice = round($forcePriceGross - $tax, 2);
            }

            if (!empty($surcharge)) {

                if (empty($forcePriceGross)) {
                    $forcePriceGross = $product->getPriceGross();
                }

                $forcePriceGross += $surcharge;
                $tax = $forcePriceGross / (1 + $product->getTaxrate() / 100) * ($product->getTaxrate() / 100);
                $forcePrice = round($forcePriceGross - $tax, 2);
            }
        }


        // Initialize
        if (empty($_SESSION['cart']['products'][$key]['productId'])) {

            $item = [
                'key' => $key,
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'amount' => 0,
                'price' => $forcePrice ?? $product->getPrice(),
                'priceGross' => $forcePriceGross ?? $product->getPriceGross(),
                'taxRate' => $product->getTaxrate(),
                'uri' => $product->getUri(),
                'shippingId' => $product->getShippingId(),
                'itemNumber' => $product->getItemNumber(),
                'customNote' => $customNote ?? null,
                'minimumAge' => $product->getMinimumAge(),
                'hasOptions' => $product->hasOptions(),
                'fieldOptions' => $options,
                'xdata' => $post->get('xdata'),
            ];

            if (!empty($variantId) and $variantId != 'default') {

                // Fetch variant
                $variant = $variantsRepository->fetchById($variantId);


                $item['variantId'] = $variant->getId();
                $item['title'] .= ' (' . $variant->getTitle() . ')';

                if (!empty($variant->getPrice())) {
                    $item['priceGross'] = $product->getPriceForVariant($variant);
                    $item['price'] = $item['priceGross'] / (1 + ($item['taxRate'] / 100));
                }
            }

            $_SESSION['cart']['products'][$key] = $item;
        }

        $amount = $post->get('amount') ?? $get->get('amount') ?? 1;

        // Increase amount of product
        $_SESSION['cart']['products'][$key]['amount'] += $amount;

        // Add equipment to item
        if (!empty($equipment = $post->get('equipment'))) {

            $item = $_SESSION['cart']['products'][$key];

            foreach ($equipment as $productId) {

                // Fetch product
                $product = $productsRepository->fetchById($productId);

                $item['equipment'][$product->getId()] = [
                    'title' => $product->getTitle(),
                    'productId' => $product->getId(),
                    'amount' => 1,
                    'price' => $product->getPrice(),
                    'priceGross' => $product->getPriceGross(),
                    'taxRate' => $product->getTaxrate(),
                    'uri' => $product->getUri()
                ];
            }

            $_SESSION['cart']['products'][$key] = $item;
        }

        // Add auto equipment to item
        if (!empty($product->getConfig('equipment'))) {

            foreach ($product->getConfig('equipment') as $equipment) {

                if (!empty($equipment['autoAddToCart'])) {

                    // Fetch product
                    $equipmentProduct = $productsRepository->fetchById($equipment['productId']);

                    if (empty($equipment['forceAmount'])) {

                        $item = $_SESSION['cart']['products'][$key];

                        $item['equipment'][$equipmentProduct->getId() . '-auto'] = [
                            'title' => $equipmentProduct->getTitle(),
                            'productId' => $equipmentProduct->getId(),
                            'amount' => 1,
                            'price' => (empty($equipment['noExtraCharge']) ? $equipmentProduct->getPrice() : 0),
                            'priceGross' => (empty($equipment['noExtraCharge']) ? $equipmentProduct->getPriceGross() : 0),
                            'taxRate' => (empty($equipment['noExtraCharge']) ? $equipmentProduct->getTaxrate() : 0),
                            'uri' => $equipmentProduct->getUri(),
                        ];

                        $_SESSION['cart']['products'][$key] = $item;
                    }
                    else {

                        $toCreate = 0;

                        foreach ($_SESSION['cart']['products'] as $item) {

                            if (empty($item['boundTo'])) {
                                continue;
                            }

                            if ($item['boundTo'] != $key) {
                                continue;
                            }

                            ++$toCreate;
                        }

                        $toCreate = $_SESSION['cart']['products'][$key]['amount'] - $toCreate;

                        for ($i = 1; $i <= $toCreate; ++$i) {

                            // Add equipment as seperate product to cart
                            $newKey = $key . '-' . rand(100, 999);

                            $item = [
                                'boundTo' => $key,
                                'key' => $newKey,
                                'productId' => $equipmentProduct->getId(),
                                'title' => $equipmentProduct->getTitle(),
                                'amount' => 1,
                                'price' => $equipmentProduct->getPrice(),
                                'priceGross' => $equipmentProduct->getPriceGross(),
                                'taxRate' => $equipmentProduct->getTaxrate(),
                                'uri' => $equipmentProduct->getUri(),
                                'shippingId' => $equipmentProduct->getShippingId(),
                                'itemNumber' => $equipmentProduct->getItemNumber(),
                                'customNote' => $customNote ?? null,
                                'minimumAge' => $equipmentProduct->getMinimumAge(),
                                'hasOptions' => $equipmentProduct->hasOptions(),
                            ];

                            if (!empty($equipment['noExtraCharge'])) {
                                $item['noExtraCharge'] = true;
                            }

                            $_SESSION['cart']['products'][$newKey] = $item;
                        }
                    }

                }
            }
        }

        // Set custom note
        if (!empty($post->get('customNote'))) {

            if (is_array($post->get('customNote'))) {
                $customNote = (string) null;

                foreach ($post->get('customNote') as $xkey => $value) {
                    $customNote .= $xkey . ': ' . $value . PHP_EOL;
                }
            }
            else {
                $customNote = $post->get('customNote');
            }

            $_SESSION['cart']['products'][$key]['customNote'] = $customNote;
        }

        // Fetch checkout plugin
        $checkoutPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class
            ]
        ]);

        $followUp = $get->get('followup') ?? 'popup';

        $productCount = 0;

        foreach ($_SESSION['cart']['products'] as $item) {
            $productCount += $item['amount'];
        }

        $item = new \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem($item, $product);

        $shopcart->reloadItems();

        if (!empty($configuration->get('Ext.Core.ShopSystem.CartFilter'))) {

            foreach ($configuration->get('Ext.Core.ShopSystem.CartFilter') as $filterClass) {

                if (!class_exists($filterClass)) {
                    continue;
                }

                $filter = new $filterClass($shopcart, $checkoutPlugin);

                if (method_exists($filter, 'onAddingProduct')) {
                    $container->call([ $filter, 'onAddingProduct' ], [
                        'key' => $item->getKey(),
                    ]);
                }
            }
        }

        if ($shippingCosts = $product->getShippingCosts()) {
            if ($shippingCosts->isApplicableToCertainProduct()) {

                $extraShipping = $shippingCosts->getCosts($item, $shopcart);

                if ($extraShipping > 0) {
                    $_SESSION['cart']['products'][$key]['shippingExtra'] = $extraShipping;
                }
            }
        }

        if (empty($get->get('redirect')) and empty($post->get('redirect')) and $followUp == 'popup') {

            $proceedUrl = $checkoutPlugin ? $checkoutPlugin->getActionUri('index', null, ['absolute' => true]) : null;


            $path = $this->getPath() . 'resources/private/views/Popup.html.twig';
            $view->set('proceedUrl', $proceedUrl);
            $view->set('product', $product);
            $view->set('view', $view);
            $html = $view->render($path, [
                'page' => $this,
            ]);


            $parser = new \Frootbox\View\HtmlParser($html, $container);
            $html = $container->call([ $parser, 'parse' ]);

            return new \Frootbox\View\ResponseJson([
                'popup' => [
                    'html' => $html
                ],
                'success' => 'Das Produkt wurde in den Warenkorb gelegt.',
                'continue' => $proceedUrl,
                'shopcart' => [
                    'items' => $productCount,
                ],
            ]);
        }
        else {

            if (!empty($get->get('redirect'))) {
                return new \Frootbox\View\ResponseJson([
                    'continue' => $get->get('redirect'),
                    'shopcart' => [
                        'items' => $productCount,
                    ],
                ]);
            }

            if (!empty($post->get('redirect'))) {
                return new \Frootbox\View\ResponseJson([
                    'continue' => $post->get('redirect'),
                    'shopcart' => [
                        'items' => $productCount,
                    ],
                ]);
            }

            $action = $get->get('proceedTo') ? $get->get('proceedTo') : 'index';

            return new \Frootbox\View\ResponseJson([
                'success' => 'Das Produkt wurde in den Warenkorb gelegt.',
                'continue' => ($checkoutPlugin ? $checkoutPlugin->getActionUri($action, null, [ 'absolute' => true ]) : null),
                'shopcart' => [
                    'items' => $productCount,
                ],
            ]);
        }
    }

    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getState(
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
    ): Response
    {
        return new \Frootbox\View\ResponseJson([
            'shopcart' => [
                'items' => $shopcart->getItemCount(),
            ],
        ]);
    }
}
