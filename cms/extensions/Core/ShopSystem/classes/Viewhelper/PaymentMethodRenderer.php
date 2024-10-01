<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Viewhelper;

class PaymentMethodRenderer extends \Frootbox\View\Viewhelper\AbstractViewhelper
{
    protected $arguments = [
        'renderPreCheckoutAction' => [
            'paymentMethod',
            'translator'
        ],
    ];

    /**
     *
     */
    public function renderPreCheckoutActionAction(
        \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod $paymentMethod,
        \Frootbox\Translation\Translator  $translator,
        \Frootbox\View\Engines\Interfaces\Engine $view,
    ): ?string
    {
        // Perform pre checkout action
        if (method_exists($paymentMethod, 'onBeforeRenderInput')) {
            $payload = $this->container->call([ $paymentMethod, 'onBeforeRenderInput' ]);
        }
        else {
            $payload = [];
        }

        $payload['t'] = $translator;

        $file = $paymentMethod->getPath() . 'resources/private/views/PreCheckout.html.twig';

        $payload['PaymentMethod'] = $paymentMethod;

        $html = $view->render($file, $payload);

        return $html;
    }
}
