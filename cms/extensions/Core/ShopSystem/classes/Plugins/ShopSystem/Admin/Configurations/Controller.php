<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Configurations;

use Frootbox\Admin\Controller\Response;
use Frootbox\Admin\View;
use Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod;
use Frootbox\Ext\Core\ShopSystem\NewsletterConnectors\Connector;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
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
    public function getForms(
        \Frootbox\Ext\Core\ContactForms\Persistence\Repositories\Forms $formsRepository
    ): \Frootbox\Db\Result
    {
        // Fetch addresses
        $result = $formsRepository->fetch([

        ]);

        return $result;
    }

    /**
     *
     */
    public function ajaxModalHelpMailPlaceholderAction(): Response
    {
        return self::getResponse('plain', 200);
    }

    /**
     *
     */
    public function ajaxModalPaymentMethodComposeAction(

    ): Response
    {


        return self::getResponse('plain', 200);
    }

    /**
     *
     */
    public function ajaxUpdatePaymentMethodsAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        $this->plugin->unsetConfig('paymentmethods');

        $paymentMethods = [];

        foreach ($post->get('paymentmethods') as $paymentMethod => $state) {
            $paymentMethods[] = $paymentMethod;
        }

        $this->plugin->addConfig([
            'paymentmethods' => $paymentMethods
        ]);

        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateCheckoutAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Set new config
        $this->plugin->unsetConfig('skipSelfpickup');
        $this->plugin->addConfig($post->get('config'));
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateGeneralAction(
        \Frootbox\Http\Post $post
    ): Response
    {
        // Set new config
        $this->plugin->addConfig($post->get('config'));
        $this->plugin->save();

        return self::getResponse('json');
    }

    /**
     *
     */
    public function ajaxUpdateVatAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
    ): Response
    {
        // Fetch targeted products
        $result = $productsRepository->fetch([
            'where' => [
                'taxrate' => $post->get('sourceVat'),
            ],
        ]);

        $loop = 0;

        foreach ($result as $product) {

            // Keep gross price
            if (!empty($post->get('keepGrossPrice'))) {

                // Calculate new price
                $price = $product->getPriceGross() / (1 + ($post->get('targetVat') / 100));

                $product->setPrice($price);
            }

            $product->setTaxrate($post->get('targetVat'));
            $product->save();

            ++$loop;
        }

        return self::getResponse('json', 200, [
            'success' => sprintf('Es wurden %s Produkte aktualisiert.', $loop),
        ]);
    }

    /**
     * @param View $view
     * @return Response
     */
    public function indexAction(

    ): Response
    {
        // Obtain payment method
        $paymentMethods = PaymentMethod::getMethods();

        // Obtain newsletter connectors
        $newsletterConnectors = Connector::getConnectors();

        return self::getResponse('html', 200, [
            'paymentMethods' => $paymentMethods,
            'newsletterConnectors' => $newsletterConnectors
        ]);
    }
}
