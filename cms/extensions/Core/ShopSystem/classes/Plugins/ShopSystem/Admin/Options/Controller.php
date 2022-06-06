<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Options;

use Frootbox\Admin\Controller\Response;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     * Get controllers root path
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\DatasheetOptionGroup $datasheetOptionGroupRepository,
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch product
        $product = $productRepository->fetchById($get->get('productId'));

        // Fetch group
        $group = $datasheetOptionGroupRepository->fetchById($get->get('groupId'));

        // Insert new option
        $option = $optionRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\Option([
            'productId' => $product->getId(),
            'groupId' => $group->getId(),
            'title' => $post->get('title'),
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#optionsReceiver_' . $group->getId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Options\Partials\OptionsList\Partial::class, [
                    'highlight' => $option->getId(),
                    'group' => $group,
                    'product' => $product,
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Fetch option
        $option = $optionRepository->fetchById($get->get('optionId'));

        $option->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#optionsReceiver_' . $option->getGroupId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Options\Partials\OptionsList\Partial::class, [
                    'group' => $option->getGroup(),
                    'product' => $option->getProduct(),
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
    ): Response
    {
        // Validate required input
        $post->require([ 'title' ]);

        // Fetch option
        $option = $optionRepository->fetchById($get->get('optionId'));


        $option->setTitle($post->get('title'));
        $option->setSurcharge($post->get('surcharge'));

        $option->save();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#optionsReceiver_' . $option->getGroupId(),
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Options\Partials\OptionsList\Partial::class, [
                    'highlight' => $option->getId(),
                    'group' => $option->getGroup(),
                    'product' => $option->getProduct(),
                    'plugin' => $this->plugin,
                ]),
            ],
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        return self::getResponse('plain', 200, [
            'product' => $product,
        ]);
    }

    /**
     *
     */
    public function ajaxModalDetailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
    ): Response
    {
        // Fetch option
        $option = $optionRepository->fetchById($get->get('optionId'));

        return self::getResponse('plain', 200, [
            'option' => $option,
        ]);
    }

    /**
     *
     */
    public function ajaxModalGroupComposeAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Fetch product
        $product = $productsRepository->fetchById($get->get('productId'));

        return self::getResponse('plain', 200, [
            'product' => $product,
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Manufacturers $manufacturersRepository
    ): Response
    {
        // Fetch datasheet
        $manufacturer = $manufacturersRepository->fetchById($get->get('manufacturerId'));

        return self::getResponse('html', 200, [
            'manufacturer' => $manufacturer
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
