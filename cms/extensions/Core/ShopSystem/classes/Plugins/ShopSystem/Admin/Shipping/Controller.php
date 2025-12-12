<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping;

use Frootbox\Admin\Controller\Response;
use Frootbox\Http\Post;

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
    public function ajaxCopyToAllAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));

        foreach ($productsRepository->fetch() as $product) {

            $product->setShippingId($shippingCosts->getId());
            $product->save();
        }

        return new Response('json', 200, [
            'success' => 'Die Versandkostenart wurde auf alle Produkte übertragen.',
        ]);
    }

    /**
     *
     */
    public function ajaxCreateAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        // Insert new shippingcosts
        $shippingCosts = $shippingCostsRepository->insert(new \Frootbox\Ext\Core\ShopSystem\Persistence\ShippingCosts([
            'customClass' => $post->get('shippingcosts')
        ]));

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#shippingCostsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\ShippingCostsList\Partial::class, [
                    'highlight' => $shippingCosts->getId(),
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxDeleteAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository
    ): Response
    {
        // Fetch shippingcosts
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));

        $shippingCosts->delete();

        // Compose response
        return self::getResponse('json', 200, [
            'modalDismiss' => true,
            'replace' => [
                'selector' => '#shippingCostsReceiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\ShippingCostsList\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxModalComposeAction (
        \Frootbox\Persistence\Repositories\Extensions $extensionsRepository
    ): Response
    {
        // Fetch available shippingcosts
        $extensions = $extensionsRepository->fetch([
            'where' => [
                'isactive' => 1,
            ],
        ]);

        $shippingcosts = [];

        foreach ($extensions as $extension) {

            $controller = $extension->getExtensionController();

            $path = $controller->getPath() . 'classes/ShippingCosts/';

            if (!file_exists($path)) {
                continue;
            }

            $dir = new \Frootbox\Filesystem\Directory($path);

            foreach ($dir as $file) {

                $class = $controller->getBaseNamespace() . 'ShippingCosts\\' . $file . '\\ShippingCosts';
                $shippingCosts = new $class;

                $shippingcosts[] = [
                    'class' => $controller->getBaseNamespace() . 'ShippingCosts\\' . $file . '\\ShippingCosts',
                    'title' => $shippingCosts->getTitle(),
                ];
            }
        }

        return self::getResponse('plain', 200, [
            'shippingcosts' => $shippingcosts
        ]);
    }

    /**
     * @return Response
     */
    public function ajaxModalConfigurationAction(

    ): Response
    {
        return self::getResponse('plain', 200, [

        ]);
    }

    /**
     *
     */
    public function ajaxModalEditAction (
        \Frootbox\Http\Get $get,
        \Frootbox\Admin\View $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository,
    ): Response
    {
        // Fetch shopping costs
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingId'));

        if (!empty($shippingCosts->getPath())) {

            $viewFilePath = $shippingCosts->getPath() . 'resources/private/views/Admin.html.twig';

            if (file_exists($viewFilePath)) {
                $adminHtml = $view->render($viewFilePath, null, [
                    'shippingCosts' => $shippingCosts,
                ]);
            }
        }

        return self::getResponse('plain', 200, [
            'shippingCosts' => $shippingCosts,
            'adminHtml' => $adminHtml ?? null,
        ]);
    }

    /**
     *
     */
    public function ajaxToggleDayAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingDay $shippingDayRepository,
    ): Response
    {
        // Validate date
        $date = new \DateTime($get->get('date'));

        if ($date < new \DateTime()) {
            throw new \Exception('Es können keine vergangenen Daten bearbeitet werden.');
        }

        $check = $shippingDayRepository->fetchOne([
            'where' => [
                'dateStart' => $get->get('date') . ' 00:00:00',
            ],
        ]);

        if (!empty($check)) {

            // Free date
            $check->delete();

            return self::getResponse('json', 200, [
                'success' => 'Die Daten wurden gespeichert.',
                'removeClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'blocked',
                ],
                'addClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'active',
                ],
            ]);
        }
        else {

            // Block date
            $shippingDay = new \Frootbox\Ext\Core\ShopSystem\Persistence\ShippingDay([
                'dateStart' => $get->get('date'),
            ]);

            $shippingDayRepository->insert($shippingDay);

            return self::getResponse('json', 200, [
                'success' => 'Die Daten wurden gespeichert.',
                'addClass' => [
                    'selector' => 'a[data-date="' . $get->get('date') . '"]',
                    'className' => 'blocked',
                ],
            ]);
        }
    }

    /**
     *
     */
    public function ajaxUpdateAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingCosts $shippingCostsRepository,
    ): Response
    {
        // Fetch shopping costs
        $shippingCosts = $shippingCostsRepository->fetchById($get->get('shippingCostsId'));

        // Update shipping costs
        $shippingCosts->setTitle($post->get('title'));
        $shippingCosts->save();

        $shippingCosts->updateFromPost($post);

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateConfigurationAction(
        \Frootbox\Http\Post $post,
    ): Response
    {
        $this->plugin->unsetConfig('shipping');
        $this->plugin->addConfig([
            'shipping' => $post->get('shipping'),
        ]);
        $this->plugin->save();

        return self::getResponse('json', 200, [
            'modalDismiss' => true,
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateExcludesAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp
    ): Response
    {
        $excludes = $post->get('excludes') ?? [];

        foreach ($excludes as $index => $data) {
            if (empty($data['from']) or empty($data['to'])) {
                unset($excludes[$index]);
            }
        }

        $excludes = array_values($excludes);

        $this->plugin->unsetConfig('postalExcludes');
        $this->plugin->addConfig([
            'postalExcludes' => $excludes,
        ]);
        $this->plugin->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
            'replace' => [
                'selector' => '#excludes-receiver',
                'html' => $gp->injectPartial(\Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Shipping\Partials\PostalCodesExcludes\Partial::class, [
                    'plugin' => $this->plugin
                ])
            ]
        ]);
    }

    /**
     *
     */
    public function ajaxUpdateTimesAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Admin\Viewhelper\GeneralPurpose $gp,
    ): Response
    {
        // Filter times
        $times = [];


        if (!empty($post->get('Times'))) {

            $loop = 0;

            foreach ($post->get('Times') as $time) {

                if (empty(trim($time['TimeFrom'])) and empty(trim($time['TimeTo']))) {
                    continue;
                }

                $key = ((int) str_replace(':', '', $time['TimeFrom'])) * 100 + $loop++;

                $times[$key] = $time;
            }

            ksort($times);
            $times = array_values($times);
        }

        $this->plugin->unsetConfig('shipping.times');
        $this->plugin->addConfig([
            'shipping' => [
                'times' => $times,
            ]
        ]);
        $this->plugin->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     *
     */
    public function daysAction(
        \Frootbox\Http\Get $get,
    ): Response
    {
        $date = $get->get('date') ? $get->get('date') : date('Y-m-d');

        return self::getResponse(body: [
            'date' => $date,
        ]);
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }

    /**
     *
     */
    public function timesAction(): Response
    {
        return self::getResponse();
    }
}
