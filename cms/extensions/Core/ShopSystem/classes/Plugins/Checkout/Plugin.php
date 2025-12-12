<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\Checkout;

use DI\Container;
use Frootbox\Config\Config;
use Frootbox\Http\Post;
use Frootbox\View\Response;
use Frootbox\View\ResponseJson;
use Frootbox\View\ResponseRedirect;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    protected $publicActions = [
        'index',
        'login',
        'checkout',
        'complete',
        'review',
        'choiceOfDelivery',
        'choiceOfSelfPickup',
        'choiceOfSelfPickupTime',
    ];

    /**
     * @param string $language
     * @return array|null
     */
    public function getAdditionalLanguageFiles(string $language): ?array
    {
        $list = [];

        $directory = new \Frootbox\Filesystem\Directory(realpath($this->getPath() . '../../PaymentMethods'));

        foreach ($directory as $file) {

            if ($file->getName() == 'PaymentMethod.php') {
                continue;
            }

            $languageFile = $file->getPath() . $file->getName() . '/resources/private/language/' .$language . '.php';

            if (file_exists($languageFile)) {
                $list[] = [
                    'file' => $languageFile,
                    'scope' => 'Core\ShopSystem\PaymentMethods\\' . $file->getName(),
                ];
            }
        }

        return $list;
    }

    /**
     * @param string $layout
     * @return string
     */
    public function getBaseActionView(string $layout): string
    {
        return $this->getPath() . 'Layouts/' . $layout . '/View.html.twig';
    }

    /**
     * @param Config $configuration
     * @return array
     */
    public function getCountries(
        \Frootbox\Config\Config $configuration,
    ): array
    {
        if (!empty($configuration->get('Ext.Core.ShopSystem.Countries'))) {
            return $configuration->get('Ext.Core.ShopSystem.Countries')->getData();
        }

        if (!empty($configuration->get('shop.shipping.countries'))) {
            return $configuration->get('shop.shipping.countries')->getData();
        }

        return [];
    }

    /**
     *
     */
    public function getNewsletterConnector(
        Container $container
    ) {
        $newsletterConnector = $this->getShopConfig('newsletterConnector');

        if (empty($newsletterConnector)) {
            return null;
        }

        $newsletterConnector = $container->get($newsletterConnector);

        return $newsletterConnector;
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
    public function getPaymentMethodOutput(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod $method
    ): ?string {
        // Obtain view file
        $viewFile = $method->getPath() . 'resources/private/views/Output.html.twig';

        if (!file_exists($viewFile)) {
            return null;
        }

        return $view->render($viewFile);
    }

    /**
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod $method
     * @return string|null
     */
    public function getPaymentMethodOutputSave(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod $method
    ): ?string {
        // Obtain view file
        $viewFile = $method->getPath() . 'resources/private/views/OutputSave.html.twig';

        if (!file_exists($viewFile)) {
            $viewFile = $method->getPath() . 'resources/private/views/Output.html.twig';
        }

        if (!file_exists($viewFile)) {
            return null;
        }

        return $view->render($viewFile);
    }

    /**
     *
     */
    public function getPaymentMethod(
        Container $container,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): ?\Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod
    {
        if (empty($_SESSION['cart']['paymentmethod'])) {

            $paymentmethods = $this->getPaymentMethods($container);


            if (empty($paymentmethods)) {
                return null;
            }

            $paymentMethod = $paymentmethods[0];


            $_SESSION['cart']['paymentmethod']['methodClass'] = get_class($paymentMethod);

            return $paymentMethod;
        }

        return new $_SESSION['cart']['paymentmethod']['methodClass'];
    }

    /**
     *
     */
    public function getPaymentxMethod(
        Container $container,
        // \Frootbox\TranslatorFactory $factory,
        // \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): array
    {
        return $this->getPaymentMethods($container);
    }

    /**
     * @param \DI\Container $container
     * @return array
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function getPaymentMethods(
        Container $container,
    ): array
    {
        $factory = $container->get(\Frootbox\TranslatorFactory::class);
        $contentElementsRepository = $container->get(\Frootbox\Persistence\Content\Repositories\ContentElements::class);

        // Generate translator
        $translator = $factory->get(GLOBAL_LANGUAGE);

        if (!empty($this->getConfig('BasePluginId'))) {

            // Fetch shop system plugin
            $plugin = $contentElementsRepository->fetchById($this->getConfig('BasePluginId'));
        }
        else {

            // Fetch shop system plugin
            $plugin = $contentElementsRepository->fetchOne([
                'where' => [
                    'className' => 'Frootbox\\Ext\\Core\\ShopSystem\\Plugins\\ShopSystem\\Plugin'
                ],
            ]);
        }

        if (empty($plugin) or empty($plugin->getConfig('paymentmethods'))) {
            return [];
        }

        $configuration = $container->get(\Frootbox\Config\Config::class);

        $paymentmethods = [];
        $loop = 0;

        foreach ($plugin->getConfig('paymentmethods') as $paymentMethodClass) {

            ++$loop;

            // Obtain payment method
            $paymentMethod = new $paymentMethodClass;

            // Load language file
            $scope = str_replace('\\', '.', substr(substr(get_class($paymentMethod), 0, -7), 13));
            $translator->setScope($scope);

            $paymentConfig = $configuration->get('Ext.Core.ShopSystem.PaymentMethods.' . $paymentMethod->getId());
            $config = !empty($paymentConfig) ? $paymentConfig->getData() : [];
            $priority = $config['Priority'] ?? 1;

            $priority *= 100;
            $priority += $loop;


            $path = $paymentMethod->getPath() . 'resources/private/language/' . GLOBAL_LANGUAGE . '.php';

            if (!file_exists($path)) {
                $path = $paymentMethod->getPath() . 'resources/private/language/de-DE.php';
            }

            $translator->addResource($path, $scope, false);

            $paymentMethod->setTitle($translator->translate('Method.Title'));

            if (!empty($_SESSION['cart']['paymentmethod']['methodClass']) and $paymentMethodClass == '\\' . $_SESSION['cart']['paymentmethod']['methodClass']) {
                $paymentMethod->setActive();
            }

            $paymentmethods[$priority] = $paymentMethod;
        }

        krsort($paymentmethods);

        return array_values($paymentmethods);
    }

    /**
     *
     */
    public function getShopConfig(string $path)
    {
        $shopPlugin = $this->getShopPlugin();

        return $shopPlugin->getConfig($path);
    }

    /**
     * @return \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin
     */
    public function getShopPlugin(): \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin
    {
        // Fetch shop-system plugin
        $contentElementsRepository = $this->db->getRepository(\Frootbox\Persistence\Content\Repositories\ContentElements::class);

        if (!empty($this->getConfig('BasePluginId'))) {

            // Fetch shop system plugin
            $plugin = $contentElementsRepository->fetchById($this->getConfig('BasePluginId'));
        }
        else {

            $plugin = $contentElementsRepository->fetchOne([
                'where' => [
                    'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin::class,
                ],
            ]);
        }


        return $plugin;
    }

    public function hasPaymentExtraStep(
        Shopcart $shopCart,
        Container $container,
    ): bool
    {
        // Get payment methods
        $paymentMethods = $this->getPaymentMethods($container);

        /**
         * @var \Frootbox\Ext\Core\ShopSystem\PaymentMethods\PaymentMethod $paymentMethod
         */
        foreach ($paymentMethods as $paymentMethod) {

            if ($paymentMethod->forcesPaymentExtraStep()) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     */
    public function isShopActive(): bool
    {
        // Check both from and to
        if (!empty($this->getConfig('shopInactive.from')) and !empty($this->getConfig('shopInactive.to'))) {
            $from = new \DateTime($this->getConfig('shopInactive.from'));
            $to = new \DateTime($this->getConfig('shopInactive.to'));

            if ($from->format('U') <= $_SERVER['REQUEST_TIME'] and $to->format('U') >= $_SERVER['REQUEST_TIME']) {
                return false;
            }
        } // Check from
        elseif (!empty($this->getConfig('shopInactive.from'))) {
            $from = new \DateTime($this->getConfig('shopInactive.from'));

            if ($from->format('U') <= $_SERVER['REQUEST_TIME']) {
                return false;
            }
        } // Check to
        elseif (!empty($this->getConfig('shopInactive.to'))) {
            $to = new \DateTime($this->getConfig('shopInactive.to'));

            if ($to->format('U') >= $_SERVER['REQUEST_TIME']) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     */
    public function ajaxBankCheckAction(): Response
    {
        d($this->getAttribute('iban'));
    }

    /**
     *
     */
    public function ajaxLoginAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
    ): Response {
        $post->require(['username', 'password']);

        // Fetch user
        $user = $usersRepository->fetchOne([
            'where' => [
                'email' => $post->get('username'),
            ],
        ]);

        if (empty($user)) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        if (!password_verify($post->get('password'), $user->getPassword())) {
            throw new \Frootbox\Exceptions\AccessDenied();
        }

        $session->setUser($user);

        $url = $this->getActionUri('checkout');

        return new ResponseRedirect($url);
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
     * @param \DI\Container $container
     * @param \Frootbox\Db\Db $dbms
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Session $session
     * @param \Frootbox\Builder $builder
     * @param \Frootbox\Config\Config $config
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\TranslatorFactory $translationFactory
     * @param \Frootbox\Persistence\Repositories\Files $fileRepository
     * @param \Frootbox\Persistence\Repositories\Users $usersRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator
     * @param \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport
     * @param \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
     * @return \Frootbox\View\Response
     * @throws \Frootbox\Exceptions\InputInvalid
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     * @throws \Frootbox\Exceptions\RuntimeError
     * @throws \Spipu\Html2Pdf\Exception\Html2PdfException
     */
    public function ajaxCheckoutAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Db\Db $dbms,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Builder $builder,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator,
        \Frootbox\Persistence\Content\Repositories\Texts $testRepository,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        if (empty($get->get('preChecked'))) {

            // Validate required input
            $post->require(['privacyPolicy', 'rightOfWithdrawal']);
        }

        // Check cart count
        if ($shopcart->getItemCount() == 0) {
            throw new \Frootbox\Exceptions\RuntimeError('Cart empty.');
        }

        // Check shopcart
        foreach ($shopcart->getItems() as $item) {
            $product = $productsRepository->fetchById($item->getProductId());

            foreach ($product->getDatasheet()->getGroups() as $group) {
                if (empty($item->getFieldOption($group->getId()))) {
                    // Fetch product
                    $product = $productsRepository->fetchById($item->getProductId());

                    if (!empty($group->getOptionsForProduct($product)->getCount())) {
                        throw new \Exception('Bitte bearbeiten Sie die Optionen an den Artikeln im Warenkorb.');
                    }
                }
            }
        }

        $dbms->transactionStart();

        // Fetch shop-system plugin
        $shopPlugin = $this->getShopPlugin();

        // Obtain translator
        $translator = $this->getTranslator($translationFactory);
        // @TODO: Add payment methods to language files listing via config

        // Update newsletter consent
        if (!empty($newsletterConnector = $this->getNewsletterConnector($container))) {
            $newsletterConnector->execute($post, $shopcart);
        }

        // Check stocks and update
        foreach ($shopcart->getItems() as $item) {
            $options = [];

            foreach ($item->getFieldOptions() as $option) {
                $options[$option['groupId']] = $option['optionId'];
            }

            ksort($options, SORT_NUMERIC);

            $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' . $item->getProductId(
                ) . ' AND JSON_CONTAINS(groupData, \'' . addslashes(json_encode($options)) . '\');';


            $result = $stockRepository->fetchByQuery($sql);

            foreach ($result as $stock) {
                if ($item->getAmount() > $stock->getAmount()) {
                    throw new \Exception('Out of stock.');
                }

                $stock->setAmount($stock->getAmount() - $item->getAmount());
                $stock->save();
            }
        }

        // Process coupon
        if (!empty($post->get('couponCode'))) {
            $coupon = $couponsRepository->fetchOne([
                'where' => [
                    'uid' => $post->get('couponCode')
                ],
            ]);

            if (!empty($coupon)) {
                $shopcart->couponRedeem($coupon);
            }
        }

        // Gather coupon data
        $couponData = [];

        foreach ($shopcart->getRedeemedCoupons() as $coupon) {
            $couponData[] = [
                'couponId' => $coupon->getId(),
                'redeemedValue' => $coupon->getRedeemedValue(),
                'code' => $coupon->getCode(),
            ];
        }

        // Validate payment if pre-checked
        if (!empty($get->get('preChecked'))) {

            $paymentMethod = $shopcart->getPaymentMethod();

            $container->call([ $paymentMethod, 'onValidatePaymentAfterPreCheckout']);
        }

        // Generate newly booked coupons
        foreach ($shopcart->getItems() as $item) {
            if ($item->getType() != 'GenericCoupon') {
                continue;
            }

            // Generate coupon code
            $couponCode = strtoupper(substr(md5(microtime(true)), 0, 7));

            // Compose new coupon
            $coupon = new \Frootbox\Ext\Core\ShopSystem\Persistence\Coupon([
                'pageId' => $shopPlugin->getPageId(),
                'pluginId' => $shopPlugin->getId(),
                'title' => $shopcart->getPersonal('email'),
                'uid' => $couponCode,
                'config' => [
                    'amount' => $item->getAmount(),
                    'value' => $item->getPriceGross(),
                    'taxrate' => $item->getTaxrate(),
                ],
            ]);

            $xconfig = $item->getConfig();

            $xconfig['couponCode'] = $couponCode;
            $item->setConfig($xconfig);

            $shopcart->setItem($item);

            // Insert coupon
            $couponsRepository->insert($coupon);
        }

        if (!empty($post->get('note'))) {
            $note = $post->get('note');
        }
        else {
            $note = $shopcart->getPersonal('note');
        }

        // Compose booking
        $booking = new \Frootbox\Ext\Core\ShopSystem\Persistence\Booking([
            'pluginId' => $this->getId(),
            'pageId' => $this->getPageId(),
            'state' => 'Booked',
            'title' => $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'),
            'config' => [
                'note' => $note,
                'personal' => $shopcart->getPersonalData(),
                'shipping' => $shopcart->getShippingData(),
                'billing' => $shopcart->getBillingData(),
                'payment' => $shopcart->getPaymentInfo(),
                'products' => $shopcart->getItemsRaw(),
                'additionalinput' => $post->get('additionalinput'),
                'coupons' => $couponData,
                'persistedData' => [
                    'shippingCosts' => $shopcart->getShippingCosts(),
                    'taxSections' => $shopcart->getTaxSections(),
                ],
                'ownOrderNumber' => $post->get('ownOrderNumber'),
            ],
        ]);

        if ($session->isLoggedIn()) {
            $booking->setUserId($session->getUser()->getId());
        }

        if (!IS_LOGGED_IN and !empty($post->get('password'))) {
            $user = $usersRepository->insert(
                new \Frootbox\Persistence\User([
                    'email' => $shopcart->getPersonal('email'),
                    'type' => 'User',
                ])
            );

            $user->setPassword($post->get('password'));
            $user->save();

            $booking->setUserId($user->getId());
        }

        // Insert booking
        $booking = $bookingsRepository->insert($booking);

        // Generate order number
        $orderNumber = $shopPlugin->getConfig('orderNumberTemplate') ? $shopPlugin->getConfig('orderNumberTemplate') : '{R:100-999}-{R:A-Z}-{ID}';
        $orderNumber = str_replace('{ID}', '{NR}', $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:([0-9]+)-([0-9]+)\}#', function ($match) {
            return rand($match[1], $match[2]);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:A-Z(:([0-9]+))?\}#', function ($match) {
            $length = !empty($match[2]) ? $match[2] : 1;

            $range = strtoupper(md5(microtime(true)));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:a-z(:([0-9]+))?\}#', function ($match) {
            $length = !empty($match[2]) ? $match[2] : 1;

            $range = md5(microtime(true));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = str_replace('{NR}', $booking->getId(), $orderNumber);

        $booking->addConfig([
            'orderNumber' => $orderNumber,
        ]);

        $booking->save();

        // Set order number to shopping-cart for later use in payment methods
        $shopcart->setOrderNumber($orderNumber);

        // Perform additional payment action
        $paymentMethod = $shopcart->getPaymentMethod();

        if (method_exists($paymentMethod, 'preCheckoutAction')) {
            $paymentData = $container->call([ $paymentMethod, 'preCheckoutAction' ]);

            $booking->addConfig([
                'payment' => [
                    'transactionData' => $paymentData,
                ],
            ]);

            $booking->save();
        }

        // Compose mails
        $view->set('translator', $translator);
        $view->set('shopcart', $shopcart);
        $view->set('booking', $booking);
        $view->set('orderNumber', $orderNumber);
        $view->set('serverpath', SERVER_PATH_PROTOCOL);

        if (!empty($shopPlugin->getConfig('textAbove'))) {
            $text = $shopPlugin->getConfig('textAbove');
            $text = str_replace(
                '{name}',
                $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'),
                $text
            );

            $view->set('textAbove', $text);
        }

        $view->set('textBelow', $shopPlugin->getConfig('textBelow'));

        preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

        $view->set('baseVendor', $match[1]);
        $view->set('baseExtension', $match[2]);


        $paymentInfoSave = $paymentMethod->renderSummarySave($view, $shopcart->getPaymentData());

        $view->set('paymentInfo', $paymentInfoSave);
        $view->set('netPrices', $this->getConfig('showNetPrices'));
        $view->set('currencySign', $shopPlugin->getCurrencySign());


        $builder->setPlugin($this)->setTemplate('Mail');
        $sourceSave = $builder->render('Mail.html.twig');

        $paymentInfo = $paymentMethod->renderSummary($view, $shopcart->getPaymentData());
        $view->set('paymentInfo', $paymentInfo);

        $source = $builder->render('Mail.html.twig');

        // Mark coupons redeemed
        foreach ($shopcart->getRedeemedCoupons() as $coupon) {
            if ($coupon->getValueLeft() > $coupon->getRedeemedValue()) {
                $coupon->setState('RedeemedPartially');
                $remaining = $coupon->getConfig('remaining') ?? $coupon->getValue();
                $coupon->addConfig([
                    'remaining' => ($remaining - $coupon->getRedeemedValue()),
                ]);
            } else {
                $coupon->setState('Redeemed');
            }

            $coupon->save();
        }

        // Generate invoice
        if (!empty($shopPlugin->getConfig('invoice.createOnCheckout'))) {

            $booking->addConfig([
                'invoice' => [
                    'number' => $shopPlugin->createInvoiceNumber(),
                ],
            ]);

            $booking->save();

            // Render source
            $builder->setPlugin($this)->setTemplate('Invoice');

            // $builder->setBaseFile($this->getPath() . 'resources/private/builder/Invoice.html.twig');

            // Fetch background file
            $file = $fileRepository->fetchByUid($shopPlugin->getUid('invoice-footer'));

            $pdfSource = $builder->render('Invoice.html.twig', [
                'plugin' => $this,
                'shopPlugin' => $shopPlugin,
                'booking' => $booking,
                'background' => ($file ? FILES_DIR . $file->getPath() : null),
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]);

            $tmpInvoiceFile = FILES_DIR . 'tmp/shop-invoice-' . $booking->getId() . '.pdf';

            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf(
                lang: 'de',
                margins: array(20, 30, 0, 25),
            );

            $html2pdf->writeHTML($pdfSource);

            // Write pdf
            $html2pdf->output(
                name: $tmpInvoiceFile,
                dest: 'F'
            );
        }

        // Generate confirmation of order
        if (!empty($shopPlugin->getConfig('confirmationOfOrder.createOnCheckout'))) {

            // Render source
            $builder->setPlugin($this)->setTemplate('ConfirmationOfOrder');
            // $builder->setBaseFile($this->getPath() . 'resources/private/builder/ConfirmationOfOrder.html.twig');

            // Fetch background file
            $file = $fileRepository->fetchByUid($shopPlugin->getUid('confirmationOfOrder-background'));

            // $testRepository->fetchByUid($shopPlugin->getUid('confirmationOfOrder-background'));;

            $pdfSource = $builder->render('ConfirmationOfOrder.html.twig', [
                'paymentInfoSave' => $paymentInfoSave,
                'plugin' => $this,
                'shopPlugin' => $shopPlugin,
                'booking' => $booking,
                'background' => ($file ? FILES_DIR . $file->getPath() : null),
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]);

            $tmpConfirmationOfOrderFile = FILES_DIR . 'tmp/shop-confirmationoforder-' . $booking->getId() . '.pdf';

            if (!file_exists(dirname($tmpConfirmationOfOrderFile))) {
                mkdir(dirname($tmpConfirmationOfOrderFile), 0777, true);
            }

            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf(
                lang: 'de',
                margins: array(20, 30, 0, 25),
            );

            $html2pdf->writeHTML($pdfSource);

            // Write pdf
            $html2pdf->output(
                name: $tmpConfirmationOfOrderFile,
                dest: 'F'
            );
        }

        // Check if bookings needs to be handed over to integrations
        if ($delegator->canTransferBooking()) {
            $delegator->transferBooking($booking);
        }

        $dbms->transactionCommit();

        // Compose mails
        $subject = !empty($shopPlugin->getConfig('subject')) ? $shopPlugin->getConfig('subject') : 'Shop-Bestellung';
        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject($subject);
        $mail->setBodyHtml($sourceSave);
        $mail->setReplyTo($config->get('mail.defaults.from.address'));

        if (!empty($tmpInvoiceFile)) {
            $attachment = new \Frootbox\Mail\Attachment($tmpInvoiceFile, 'Rechnung-' . $booking->getConfig('invoice.number'). '.pdf');
            $mail->addAttachment($attachment);
        }

        if (!empty($tmpConfirmationOfOrderFile)) {
            $attachment = new \Frootbox\Mail\Attachment($tmpConfirmationOfOrderFile, 'Bestellung-' . $booking->getConfig('orderNumber'). '.pdf');
            $mail->addAttachment($attachment);
        }

        $recipient = $shopcart->getBilling('email') ? $shopcart->getBilling('email') : $shopcart->getPersonal('email');

        $mail->clearTo();
        $mail->addTo($recipient);

        try {
            // Send customer
            $mailTransport->send($mail);

            $mailSent = true;
        } // Ignore exceptions from mail sending because it’s very likely a mistyped email
        catch (\PHPMailer\PHPMailer\Exception $e) {
            $mailSent = false;
        }

        $mail->clearTo();
        $mail->clearReplyTo();

        // Send moderator
        if (!empty($shopPlugin->getConfig('recipients'))) {
            $recipients = explode(',', $shopPlugin->getConfig('recipients'));
        } else {
            $recipients = [$config->get('mail.defaults.from.address')];
        }

        $mail->setSubject('KOPIE: ' . $mail->getSubject());
        $mail->setBodyHtml($source);

        foreach ($recipients as $email) {
            $mail->addTo($email);
        }

        $mail->setReplyTo($shopcart->getPersonal('email'));
        $mailTransport->send($mail);

        // Look for "booking completed" page
        $pluginCompleted = $contentElements->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Plugin::class,
            ],
        ]);

        if ($pluginCompleted) {
            $uri = $pluginCompleted->getActionUri('index');
        }
        else {
            $uri = $this->getActionUri('complete', ['bookingId' => $booking->getId(), 'mailSent' => $mailSent,]);
        }

        unset($_SESSION['cart']);

        return new \Frootbox\View\ResponseJson([
            'bookingId' => $booking->getId(),
            'mailSent' => $mailSent,
            'success' => $translator->translate('Core.ShopSystem.Plugins.Checkout.CheckoutCompleted'),
            'continue' => $uri,
        ]);
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
     * @param \DI\Container $container
     * @param \Frootbox\Db\Db $dbms
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Session $session
     * @param \Frootbox\Builder $builder
     * @param \Frootbox\Config\Config $config
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\TranslatorFactory $translationFactory
     * @param \Frootbox\Persistence\Repositories\Files $fileRepository
     * @param \Frootbox\Persistence\Repositories\Users $usersRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator
     * @param \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @return \Frootbox\View\ResponseJson
     * @throws \Frootbox\Exceptions\InputInvalid
     * @throws \Frootbox\Exceptions\RuntimeError
     * @throws \Spipu\Html2Pdf\Exception\Html2PdfException
     */
    public function ajaxCheckoutPreAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Db\Db $dbms,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Builder $builder,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): ResponseJson
    {
        if (!empty($post->get('bookingId'))) {

            return new ResponseJson([
                'isCartValid' => true,
                'bookingId' => $post->get('bookingId'),
                'bookingToken' => $post->get('bookingToken'),
            ]);
        }

        // Set note
        $shopcart->setNote($post->get('Note'));

        // Start database transaction
        $dbms->transactionStart();

        // Fetch shop-system plugin
        $shopPlugin = $this->getShopPlugin();

        // Obtain translator
        $translator = $this->getTranslator($translationFactory);

        // Update newsletter consent
        if (!empty($newsletterConnector = $this->getNewsletterConnector($container))) {
            $newsletterConnector->execute($post, $shopcart);
        }

        // Compose booking
        $booking = new \Frootbox\Ext\Core\ShopSystem\Persistence\Booking([
            'pluginId' => $this->getId(),
            'pageId' => $this->getPageId(),
            'state' => 'Booked',
            'title' => $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'),
            'uid' => $shopcart->getUniqueId(),
            'config' => [
                'note' => $shopcart->getNote(),
                'personal' => $shopcart->getPersonalData(),
                'shipping' => $shopcart->getShippingData(),
                'billing' => $shopcart->getBillingData(),
                'payment' => $shopcart->getPaymentInfo(),
                'products' => $shopcart->getItemsRaw(),
                'additionalinput' => $post->get('additionalinput'),
                'coupons' => $couponData ?? [],
                'persistedData' => [
                    'shippingCosts' => $shopcart->getShippingCosts(),
                    'taxSections' => $shopcart->getTaxSections(),
                ],
                'ownOrderNumber' => $post->get('ownOrderNumber'),
            ],
        ]);

        if ($session->isLoggedIn()) {
            $booking->setUserId($session->getUser()->getId());
        }

        if (!IS_LOGGED_IN and !empty($post->get('password'))) {
            $user = $usersRepository->insert(
                new \Frootbox\Persistence\User([
                    'email' => $shopcart->getPersonal('email'),
                    'type' => 'User',
                ])
            );

            $user->setPassword($post->get('password'));
            $user->save();

            $booking->setUserId($user->getId());
        }

        // Insert booking
        $booking = $bookingsRepository->insert($booking);

        // Generate order number
        $orderNumber = $shopPlugin->getConfig('orderNumberTemplate') ? $shopPlugin->getConfig('orderNumberTemplate') : '{R:100-999}-{R:A-Z}-{ID}';
        $orderNumber = str_replace('{ID}', '{NR}', $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:([0-9]+)-([0-9]+)\}#', function ($match) {
            return rand($match[1], $match[2]);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:A-Z(:([0-9]+))?\}#', function ($match) {
            $length = !empty($match[2]) ? $match[2] : 1;

            $range = strtoupper(md5(microtime(true)));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:a-z(:([0-9]+))?\}#', function ($match) {
            $length = !empty($match[2]) ? $match[2] : 1;

            $range = md5(microtime(true));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = str_replace('{NR}', $booking->getId(), $orderNumber);

        $booking->addConfig([
            'orderNumber' => $orderNumber,
        ]);

        $booking->save();

        // Set order number to shopping-cart for later use in payment methods
        $shopcart->setOrderNumber($orderNumber);

        // Commit database transaction
        $dbms->transactionCommit();

        // Check if bookings needs to be handed over to integrations
        if ($delegator->canTransferBooking()) {
            $delegator->transferBooking($booking);
        }

        // Compose mails
        $view->set('translator', $translator);
        $view->set('shopcart', $shopcart);
        $view->set('booking', $booking);
        $view->set('orderNumber', $orderNumber);
        $view->set('serverpath', SERVER_PATH_PROTOCOL);

        if (!empty($shopPlugin->getConfig('textAbove'))) {
            $text = $shopPlugin->getConfig('textAbove');
            $text = str_replace(
                '{name}',
                $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'),
                $text
            );

            $view->set('textAbove', $text);
        }

        $view->set('textBelow', $shopPlugin->getConfig('textBelow'));

        preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

        $view->set('baseVendor', $match[1]);
        $view->set('baseExtension', $match[2]);

        $paymentMethod = $shopcart->getPaymentMethod();
        $paymentInfoSave = $paymentMethod->renderSummarySave($view, $shopcart->getPaymentData());

        $view->set('paymentInfo', $paymentInfoSave);
        $view->set('netPrices', $this->getConfig('showNetPrices'));
        $view->set('currencySign', $shopPlugin->getCurrencySign());


        $builder->setPlugin($this)->setTemplate('Mail');
        $sourceSave = $builder->render('Mail.html.twig');

        $paymentInfo = $paymentMethod->renderSummary($view, $shopcart->getPaymentData());
        $view->set('paymentInfo', $paymentInfo);

        $source = $builder->render('Mail.html.twig');

        // Generate confirmation of order
        if (!empty($shopPlugin->getConfig('confirmationOfOrder.createOnCheckout'))) {

            // Render source
            $builder->setPlugin($this)->setTemplate('ConfirmationOfOrder');
            // $builder->setBaseFile($this->getPath() . 'resources/private/builder/ConfirmationOfOrder.html.twig');

            // Fetch background file
            $file = $fileRepository->fetchByUid($shopPlugin->getUid('confirmationOfOrder-background'));


            $pdfSource = $builder->render('ConfirmationOfOrder.html.twig', [
                'paymentInfoSave' => $paymentInfoSave,
                'plugin' => $this,
                'shopPlugin' => $shopPlugin,
                'booking' => $booking,
                'background' => ($file ? FILES_DIR . $file->getPath() : null),
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]);

            $tmpConfirmationOfOrderFile = FILES_DIR . 'tmp/shop-confirmationoforder-' . $booking->getId() . '.pdf';

            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf(
                lang: 'de',
                margins: array(20, 30, 0, 25),
            );

            $html2pdf->writeHTML($pdfSource);

            // Write pdf
            $html2pdf->output(
                name: $tmpConfirmationOfOrderFile,
                dest: 'F'
            );
        }


        // Compose mails
        $subject = !empty($shopPlugin->getConfig('subject')) ? $shopPlugin->getConfig('subject') : 'Shop-Bestellung';
        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject($subject);
        $mail->setBodyHtml($sourceSave);
        $mail->setReplyTo($config->get('mail.defaults.from.address'));

        if (!empty($tmpInvoiceFile)) {
            $attachment = new \Frootbox\Mail\Attachment($tmpInvoiceFile, 'Rechnung-' . $booking->getConfig('invoice.number'). '.pdf');
            $mail->addAttachment($attachment);
        }

        if (!empty($tmpConfirmationOfOrderFile)) {
            $attachment = new \Frootbox\Mail\Attachment($tmpConfirmationOfOrderFile, 'Bestellung-' . $booking->getConfig('orderNumber'). '.pdf');
            $mail->addAttachment($attachment);
        }

        $recipient = $shopcart->getBilling('email') ? $shopcart->getBilling('email') : $shopcart->getPersonal('email');

        $mail->clearTo();
        $mail->addTo($recipient);

        try {

            // Send customer
            $mailTransport->send($mail);
        } // Ignore exceptions from mail sending because it’s very likely a mistyped email
        catch (\PHPMailer\PHPMailer\Exception $e) {

        }

        // Backup order
        $cacheFile = FILES_DIR . 'orders/' . date('Y-m-d') . '/' . date('H-i') . '-' . rand(10000, 99999) . '.json';
        $file = new \Frootbox\Filesystem\File($cacheFile);
        $file->setSource(json_encode($_SESSION['cart']));
        $file->write();

        // unset($_SESSION['cart']);

        return new ResponseJson([
            'isCartValid' => true,
            'bookingId' => $booking->getId(),
            'bookingToken' => md5('#' . $booking->getId()),
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxDismissCouponAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
    ): Response
    {
        // Fetch coupn
        $coupon = $couponsRepository->fetchOne([
            'where' => [
                'uid' => $get->get('couponCode'),
            ],
        ]);

        if (empty($coupon)) {
            throw new \Frootbox\Exceptions\NotFound('Coupon');
        }

        $shopcart->couponDismiss($coupon);

        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial(
                'ItemsTable',
                ['plugin' => $this, 'shopcart' => $shopcart, 'editable' => false]
            ),
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @return Response
     */
    public function ajaxDropItemAction(
        Shopcart $shopcart,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper
    ): Response
    {
        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        $key = $this->getAttribute('key');

        $shopcart->dropItemByKey($key);

        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [
                'plugin' => $this,
                'shopcart' => $shopcart,
                'editable' => true,
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]),
            'shopcart' => [
                'items' => $shopcart->getItemCount(),
            ],
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
     * @return Response
     */
    public function ajaxModalOptionsAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Obtain shopcart item
        $item = $shopcart->getItem($get->get('key'));

        // Fetch product
        $product = $productsRepository->fetchById($item->getProductId());

        return new \Frootbox\View\ResponseView([
            'plugin' => $this,
            'item' => $item,
            'product' => $product,
        ]);
    }

    /**
     * @param \DI\Container $container
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator
     * @param \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @return \Frootbox\View\ResponseRedirect
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxPaymentValidateAction(
        Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElements,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): ResponseRedirect
    {
        /**
         * Fetch booking
         * @var \Frootbox\Ext\Core\ShopSystem\Persistence\Booking $booking
         */
        $booking = $bookingsRepository->fetchOne([
            'where' => [
                'uid' => $get->get('uniqueId'),
            ],
        ]);

        if ($get->get('redirect_status') == 'failed') {

            $uri = $this->getActionUri('paymentFailed', [ 'bookingId' => $booking->getId() ]);

            return new \Frootbox\View\ResponseRedirect($uri);
        }


        // Get payment method
        $paymentMethod = $booking->getPaymentMethod();

        $result = $container->call([ $paymentMethod, 'onValidatePaymentAfterCheckout' ]);

        if (!empty($result['isPaid'])) {

            // Update booking
            $booking->addConfig([
                'payment' => [
                    'state' => 'Paid',
                ],
            ]);

            $booking->save();

            // Check if bookings needs to be handed over to integrations
            if ($delegator->canTransferBooking()) {
                $delegator->updateBookingsPaymentState($booking);
            }
        }

        // Look for "booking completed" page
        $pluginCompleted = $contentElements->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\CheckoutCompleted\Plugin::class,
            ],
        ]);

        if ($pluginCompleted) {
            $uri = $pluginCompleted->getActionUri('index');
        }
        else {
            $uri = $this->getActionUri('complete', ['bookingId' => $booking->getId(), 'mailSent' => true, ]);
        }

        return new \Frootbox\View\ResponseRedirect($uri);
    }

    /**
     *
     */
    public function ajaxProceedToLoginAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Config\Config $configuration,
    ): ResponseRedirect
    {
        // Check cart filter
        if (!empty($configuration->get('Ext.Core.ShopSystem.CartFilter'))) {

            foreach ($configuration->get('Ext.Core.ShopSystem.CartFilter') as $filterClass) {

                if (is_array($filterClass)) {
                    continue;
                }

                if (!class_exists($filterClass)) {
                    continue;
                }

                $filter = new $filterClass($shopcart, $this);

                if (method_exists($filter, 'onBeforeProceedToLogin')) {
                    $response = $container->call([$filter, 'onBeforeProceedToLogin']);

                    if ($response !== null) {
                        return $response;
                    }
                }
            }
        }

        // Check for free choice of delivery day
        foreach ($shopcart->getItems() as $item) {
            if (!empty($item->getProduct()->getConfig('freeChoiceOfDeliveryDay'))) {
                return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfDelivery'));
            }
        }

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('login'));
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxRedeemCouponAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
    ): Response {
        // Fetch coupn
        $coupon = $couponsRepository->fetchOne([
            'where' => [
                'uid' => $get->get('couponCode'),
            ],
        ]);

        if (empty($coupon)) {
            throw new \Exception('Der Coupon konnte nicht gefunden werden.');
        }

        try {
            $shopcart->couponRedeem($coupon);
        } catch (\Exception $e) {
            die($e->getMessage());
        }


        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial(
                'ItemsTable',
                ['plugin' => $this, 'shopcart' => $shopcart, 'editable' => false]
            )
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param Post $post
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository
     * @return Response
     */
    public function ajaxSetOptionsAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Variants $variantsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Option $optionRepository,
    ): Response {
        // Obtain shopcart item
        $item = $shopcart->getItem($get->get('key'));
        $itemData = $_SESSION['cart']['products'][$get->get('key')];

        // Fetch product
        $product = $productsRepository->fetchById($item->getProductId());

        if (!empty($post->get('options'))) {
            $options = [];
            $optCheck = [];
            $surcharge = 0;

            foreach ($post->get('options') as $groupId => $optionId) {
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

            $itemData['fieldOptions'] = $options;

            $stock = $product->getStocks($optCheck);

            $itemData['priceGross'] = $product->getPriceGross();

            if (!empty($stock->getPrice())) {
                $itemData['priceGross'] = $stock->getPrice();
                $tax = $itemData['priceGross'] / (1 + $product->getTaxrate() / 100) * ($product->getTaxrate() / 100);
                $itemData['price'] = round($itemData['priceGross'] - $tax, 2);
            }

            if (!empty($itemData['noExtraCharge']) and !empty($surcharge)) {
                $itemData['priceGross'] = $surcharge;
                $tax = $itemData['priceGross'] / (1 + $product->getTaxrate() / 100) * ($product->getTaxrate() / 100);
                $itemData['price'] = round($itemData['priceGross'] - $tax, 2);
                $itemData['hasSurcharge'] = true;
            } elseif (!empty($surcharge)) {
                $itemData['priceGross'] += $surcharge;
                $tax = $itemData['priceGross'] / (1 + $product->getTaxrate() / 100) * ($product->getTaxrate() / 100);
                $itemData['price'] = round($itemData['priceGross'] - $tax, 2);
            }

            if (!empty($itemData['variantId']) and $itemData['variantId'] != 'default') {
                // Fetch variant
                $variant = $variantsRepository->fetchById($itemData['variantId']);


                if (!empty($variant->getPrice())) {
                    $product->getPriceForVariant($variant);

                    $itemData['priceGross'] = $product->getPriceForVariant($variant);
                    $itemData['price'] = $itemData['priceGross'] / (1 + ($itemData['taxRate'] / 100));
                }
            }
        }

        $item = new \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\ShopcartItem($itemData);

        $shopcart->setItem($item);

        $partialsViewhelper->setParameters([
            'plugin' => $this
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial(
                'ItemsTable',
                ['plugin' => $this, 'shopcart' => $shopcart, 'editable' => true]
            ),
            'item' => [
                'key' => $item->getKey(),
                'total' => round($item->getTotal(), 2),
            ],
            'shopcart' => [
                'items' => $shopcart->getItemCount(),
                'total' => round($shopcart->getTotal(), 2),
            ],
        ]);
    }

    /**
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\View\Viewhelper\Delegator $delegator
     * @return Response
     */
    public function ajaxSetPaymentMethodAction(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\View\Viewhelper\Delegator $delegator
    ): Response {
        $_SESSION['cart']['paymentmethod']['methodClass'] = $this->getAttribute('paymentMethod');

        $paymentMethod = new $_SESSION['cart']['paymentmethod']['methodClass'];

        $delegator->setObject($paymentMethod);

        $view->set('plugin', $this);

        return new \Frootbox\View\ResponseJson([
            'method' => [
                'html' => $delegator->renderInputHtml(),
                'class' => $_SESSION['cart']['paymentmethod']['methodClass'],
            ],
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param Container $container
     * @param Post $post
     * @param Config $config
     * @param Config $configuration
     * @param \Frootbox\TranslatorFactory $translationFactory
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxSubmitDataAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        \Frootbox\Config\Config $configuration,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Validate required input
        $required = [
            'personal.firstname',
            'personal.lastname',
            'personal.street',
            'personal.streetNumber',
            'personal.postalCode',
            'personal.city',
        ];

        if (empty($this->getShopConfig('fields.SkipGender'))) {
            $required[] = 'personal.gender';
        }

        $shipping = $post->get('shipping');

        if (empty($this->getConfig('skipShipping')) and $shipping['type'] == 'shipToAddress') {
            $required = array_merge($required, [
                'shipping.firstname',
                'shipping.lastname',
                'shipping.street',
                'shipping.streetNumber',
                'shipping.postalCode',
                'shipping.city',
            ]);
        }

        // Validate required fields
        $post->require($required);

        // Obtain translator
        $translator = $this->getTranslator($translationFactory);

        // Set data
        $shopcart->setPersonal($post->get('personal'));

        // Update billing
        if (!empty($post->get('billing'))) {

            $billing = $shopcart->getBillingData();
            $billing = array_merge($billing, $post->get('billing'));
            $shopcart->setBilling($billing);
        }

        // Update shipping
        $shipping = $post->get('shipping');
        $shipping['preferredDeliveryWindow'] = $_SESSION['cart']['shipping']['preferredDeliveryWindow'] ?? null;
        $shipping['costsExtra'] = $_SESSION['cart']['shipping']['costsExtra'] ?? null;
        $shipping['deliveryDay'] = $shopcart->getShipping('deliveryDay');
        $shipping['pickupDay'] = $shopcart->getShipping('pickupDay');
        $shipping['pickupTime'] = $shopcart->getShipping('pickupTime');

        $shopcart->setShipping($shipping);

        // Check shopcart
        foreach ($shopcart->getItems() as $item) {
            $product = $productsRepository->fetchById($item->getProductId());

            foreach ($product->getDatasheet()->getGroups() as $group) {
                if (empty($item->getFieldOption($group->getId()))) {
                    // Fetch product
                    $product = $productsRepository->fetchById($item->getProductId());

                    if (!empty($group->getOptionsForProduct($product)->getCount())) {
                        throw new \Exception('Bitte bearbeiten Sie die Optionen an den Artikeln im Warenkorb.');
                    }
                }
            }
        }

        if (!empty($config->get('Ext.Core.ShopSystem.CartFilter'))) {
            foreach ($config->get('Ext.Core.ShopSystem.CartFilter') as $filterClass) {

                $filter = new $filterClass($shopcart, $this);

                if (method_exists($filter, 'onAfterSubmitPersonalData')) {
                    $response = $container->call([$filter, 'onAfterSubmitPersonalData']);

                    if ($response instanceof \Frootbox\View\ResponseRedirect) {
                        return $response;
                    }
                }
            }
        }


        // Validate shipping costs
        $shopcart->updateShippingCosts();
        $method = $shopcart->getShippingMethod();

        if ($method) {

            if (!$method->isShippingAddressValid($shopcart)) {
                throw new \Exception($translator->translate('Core.ShopSystem.Plugins.Checkout.Exceptions.ZipcodeAreaNoDelivery'));
            }
        }

        if ($this->hasPaymentExtraStep($shopcart, $container)) {

            return new \Frootbox\View\ResponseJson([
                'redirect' => $this->getActionUri('payment'),
            ]);
        }

        if (!empty($this->getConfig('PaymentExtraStep'))) {

            return new \Frootbox\View\ResponseJson([
                'redirect' => $this->getActionUri('selectPayment'),
            ]);
        }

        if (empty($post->get('payment')['method'])) {
            throw new \Exception('Die Zahlungsart muss gewählt werden.');
        }

        // Update payment method
        $shopcart->setPaymentMethodClass($post->get('payment')['method']);

        // Redirect to payment if no choice of deliver<
        $paymentMethod = $shopcart->getPaymentMethod();

        if (method_exists($paymentMethod, 'postPaymentSelectionAction')) {

            $result = $container->call([$paymentMethod, 'postPaymentSelectionAction'], [
                'config' => $config,
                'shopcart' => $shopcart,
            ]);

            if (!empty($result['redirect'])) {
                return new \Frootbox\View\ResponseJson([
                    'redirect' => $result['redirect'],
                ]);
            }
        }

        return new \Frootbox\View\ResponseJson([
            'redirect' => $this->getActionUri('review'),
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param Container $container
     * @param \Frootbox\Http\Get $get
     * @param Post $post
     * @return ResponseJson
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxSubmitPaymentAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
    ): ResponseJson
    {
        // Get payment method
        $paymentMethodClass = $post->get('PaymentMethod');
        $paymentMethod = new $paymentMethodClass;

        if ($shopcart->getPaymentMethodClass() != $paymentMethodClass and method_exists($paymentMethod, 'onUpdated')) {
            $container->call([ $paymentMethod, 'onUpdated' ]);
        }

        // Update payment method
        $shopcart->setPaymentMethodClass($paymentMethodClass);

        if ($paymentMethod->isForcingNewPaymentFlow()) {
            $url = $this->getActionUri('reviewFlow');
        }
        else {
            $url = $this->getActionUri('review');
        }

        return new \Frootbox\View\ResponseJson([
            'redirect' => $url,
        ]);
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Config\Config $config
     * @return \Frootbox\View\Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxUpdateDeliveryDayAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
    ): Response
    {
        if (empty($post->get('deliveryDay')) and empty($get->get('deliveryDay'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfDelivery'));
        }

        // Obtain shipping data
        $shipping = $shopcart->getShippingData();
        $shipping['deliveryDay'] = $post->get('deliveryDay') ?? $get->get('deliveryDay');

        $shopcart->setShipping($shipping);

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout'));
    }

    /**
     *
     */
    public function ajaxUpdatePickupDayAction(
        Shopcart $shopcart,
        Container $container,
        \Frootbox\Http\Get $get,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
    ): Response
    {
        if (empty($post->get('pickupDay')) and empty($get->get('pickupDay'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfPickupDay'));
        }

        // Obtain shipping data
        $shipping = $shopcart->getShippingData();

        $shipping['pickupDay'] = !empty($post->get('pickupDay')) ? $post->get('pickupDay') : $get->get('pickupDay');

        $shopcart->setShipping($shipping);

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('ChoiceOfSelfPickupTime'));
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
     * @param \Frootbox\Http\Post $post
     * @return \Frootbox\View\Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function ajaxUpdateDeliveryTimeAction(
        Shopcart $shopcart,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickUpTimeRepository,
    ): Response
    {
        try {

            if (empty($post->get('PickupTime'))) {
                throw new \Exception('InputMissing.Fields');
            }

            /**
             * Fetch pickup-time
             * @var \Frootbox\Ext\Core\ShopSystem\Persistence\SelfPickupTime $pickUpTime
             */
            $pickUpTime = $selfPickUpTimeRepository->fetchById($post->get('PickupTime'));

            // Obtain shipping data
            $shipping = $shopcart->getShippingData();

            $shipping['pickupTime'] = $pickUpTime->getTimeStart() . '–' . $pickUpTime->getTimeEnd();
            $shipping['pickupTimeId'] = $pickUpTime->getId();

            $shopcart->setShipping($shipping);

            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout'));
        }
        catch (\Exception $e) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('ChoiceOfSelfPickupTime', [ 'error' => $e->getMessage() ]));
        }
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @return Response
     */
    public function ajaxUpdateItemAmountAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper,
    ): Response
    {
        // Obtain item
        $item = $shopcart->getItem($get->get('key'));
        $item->setAmount($get->get('amount'));

        // Update item
        $shopcart->setItem($item);

        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [
                'plugin' => $this,
                'shopcart' => $shopcart,
                'editable' => true,
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]),
            'item' => [
                'key' => $item->getKey(),
                'total' => round($item->getTotal(), 2),
            ],
            'shopcart' => [
                'items' => $shopcart->getItemCount(),
                'total' => round($shopcart->getTotal(), 2),
            ],
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Http\Get $get
     * @param \Frootbox\View\Viewhelper\Partials $partialsViewhelper
     * @return Response
     */
    public function ajaxUpdateShippingMethodAction(
        Shopcart $shopcart,
        \Frootbox\Http\Get $get,
        \Frootbox\View\Viewhelper\Partials $partialsViewhelper,
    ): Response
    {
        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        $shipping = $shopcart->getShippingData();
        $shipping['type'] = $get->get('shipping')['type'];
        $shipping['country'] = $get->get('shipping')['country'] ?? 'DE';

        $shopcart->setShipping($shipping);


        $personal = $shopcart->getPersonalData();
        $personal['country'] = $get->get('personal')['country'] ?? 'DE';

        $shopcart->setPersonal($personal);

        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [
                'plugin' => $this,
                'shopcart' => $shopcart,
                'editable' => true,
                'currencySign' => $shopPlugin->getCurrencySign(),
            ]),
        ]);
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

        return new ResponseJson([
            'stocks' => $stocks,
        ]);
    }

    /**
     * @return Response
     */
    public function cancelAction(): Response
    {
        unset($_SESSION['cart']);

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
    }

    /**
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @return Response
     */
    public function completeAction(
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        // Fetch booking
        $booking = $bookingsRepository->fetchById($this->getAttribute('bookingId'));

        return new Response([
            'booking' => $booking,
            'shopPlugin' => $shopPlugin,
            'currencySign' => $shopPlugin->getCurrencySign(),
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Session $session
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository
     * @return Response
     */
    public function checkoutAction(
        Shopcart $shopcart,
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\PickupLocation $pickupLocationRepository,
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        if (IS_LOGGED_IN) {

            // Obtain user
            $user = $session->getUser();

            // Fetch addresses
            $addresses = $addressesRepository->fetch([
                'where' => [
                    'uid' => $user->getUid('shop-addess'),
                ],
            ]);
        }

        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        // Fetch pickup locations
        $pickupLocations = $pickupLocationRepository->fetch();

        return new Response([
            'user' => $user ?? null,
            'shopcart' => $shopcart,
            'addresses' => $addresses ?? [],
            'shopPlugin' => $shopPlugin,
            'currencySign' => $shopPlugin->getCurrencySign(),
            'pickupLocations' => $pickupLocations,
        ]);
    }

    /**
     *
     */
    public function choiceOfDeliveryAction(
        Shopcart $shopcart,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\ShippingDay $shippingDayRepository,
    ): Response
    {
        if ($shopcart->getShipping('type') == 'selfPickup') {
            return new ResponseRedirect($this->getActionUri('choiceOfSelfPickup'));
        }

        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        $selectedMonth = $post->get('month') ?? $shopcart->getShipping('deliveryDay');

        $now = new \DateTime();

        if (!empty($selectedMonth)) {
            $date = new \DateTime($selectedMonth);
            $selectedMonthString = $date->format('Y-m');
            $selectedDayString = $date->format('Y-m-d');
        }
        else {
            $date = new \DateTime();
        }

        if ($date->format('Y-m') == $now->format('Y-m')) {
            $date->setDate($date->format('Y'), $date->format('m'), $now->format('d'));
        }
        else {
            $date->setDate($date->format('Y'), $date->format('m'), 1);
        }

        // Compute months
        $months = [];
        $monthDate = new \DateTime(date('Y-m') . '-01');

        for ($i = 1; $i <= 3; ++$i) {
            $months[] = clone $monthDate;

            $monthDate->modify('+1 month');
        }

        $firstRegularDay = new \DateTime();

        if (!empty($shopPlugin->getConfig('shipping.skipNextWorkdays'))) {

            $addedDays = $shopPlugin->getConfig('shipping.skipNextWorkdays');

            if ($firstRegularDay->format('N') == 6) {
               // $addedDays += 2;
            }
            else if ($firstRegularDay->format('N') == 7) {
               // $addedDays += 1;
            }

            $firstRegularDay->modify('+' . $addedDays . ' days');
        }

        if (!empty($shopPlugin->getConfig('shipping.skipNextHours'))) {

            $addedHours = $shopPlugin->getConfig('shipping.skipNextHours');

            $firstRegularDay->modify('+' . $addedHours . ' hours');
        }


        $lastDate = new \DateTime($date->format('Y-m-d'));
        $lastDate->modify('+ ' . (($date->format('t') - $date->format('d')) + 1) . ' days');

        $period = new \DatePeriod(
            $date,
            new \DateInterval('P1D'),
            $lastDate,
        );

        $dayList = [];
        $dayCheck = [];

        // Exclude public holidays
        $country = ($shopcart->getShipping('type') == 'shipToBillingAddress') ? $shopcart->getPersonal('country') : $shopcart->getShipping('country');
        $postalCode = (int) (($shopcart->getShipping('type') == 'shipToBillingAddress') ? $shopcart->getPersonal('postalCode') : $shopcart->getShipping('postalCode'));

        $publicHolidays = [];

        if (!empty($country)) {

            $statesConfigFile = $this->getPath() . '/../../../resources/private/states/' . strtolower($country) . '.php';

            if (file_exists($statesConfigFile)) {

                $data = require $statesConfigFile;
                $stateShort = null;

                foreach ($data['postalCodes'] as $postalCodeData) {

                    if ($postalCode >= $postalCodeData['from'] and $postalCode <= $postalCodeData['to']) {
                        $stateShort = $postalCodeData['stateShort'];
                        break;
                    }
                }

                if (!empty($stateShort)) {
                    $url = 'https://get.api-feiertage.de?years=' . $date->format('Y') . '&states=' . strtolower($stateShort);

                    $response = file_get_contents($url);
                    $response = json_decode($response, true);

                    $publicHolidays = $response['feiertage'];
                }
            }
        }

        foreach ($period as $key => $actDate) {

            $dateString = $actDate->format('Y-m-d');

            // Check public holidays
            foreach ($publicHolidays as $holiday) {
                if ($dateString == $holiday['date']) {
                   continue 2;
                }
            }

            if ($firstRegularDay > $actDate) {
                continue;
            }

            $isBlocked = $shippingDayRepository->fetchOne([
                'where' => [
                    'dateStart' => $dateString . ' 00:00:00',
                ],
            ]);

            if ($isBlocked) {
                continue;
            }

            if (!empty($shopPlugin->getConfig('shipping.regularShippingDays')) and !in_array($actDate->format('N'), $shopPlugin->getConfig('shipping.regularShippingDays'))) {
                continue;
            }

            // Check excluded delivery days
            if (!empty($shopPlugin->getConfig('postalExcludes'))) {

                if ($postalCode < 10000) {
                    $checkPostalCode = str_pad((int) $postalCode, 5, '0');
                }
                else {
                    $checkPostalCode = $postalCode;
                }

                foreach ($shopPlugin->getConfig('postalExcludes') as $postalCodeRange) {

                    $from = str_pad((int) $postalCodeRange['from'], 5, '0');
                    $to = str_pad((int) $postalCodeRange['to'], 5, '0');

                    if ($checkPostalCode >= $from and $checkPostalCode <= $to) {

                        $weekDay = $actDate->format('w');

                        if (!empty($postalCodeRange['days'][$weekDay])) {
                            continue 2;
                        }
                    }
                }
            }

            $dayList[] = $actDate;
            $dayCheck[$actDate->format('Y-m-d')] = true;
        }

        $weeks = [];

        $runningDate = clone $date;
        $runningDate->modify('first day of this month');

        $firstDayNum = $runningDate->format('N');

        $runningDate->modify('-' . ($firstDayNum - 1) . ' days');

        $checkMonth = $date->format('n');

        for ($w = 0; $w < 6; ++$w) {

            $week = [
                'nr' => (int) $runningDate->format('W'),
                'days' => [],
            ];

            for ($d = 1; $d <= 7; ++$d) {

                $day = [
                    'date' => $runningDate->format('Y-m-d'),
                    'isActive' => !empty($dayCheck[$runningDate->format('Y-m-d')]),
                ];

                $week['days'][] = $day;

                $runningDate->modify('+ 1 day');
            }

            $weeks[] = $week;

            if ($checkMonth != $runningDate->format('n')) {
                break;
            }
        }


        return new Response([
            'selectedMonth' => $selectedMonth,
            'selectedMonthString' => $selectedMonthString ?? null,
            'selectedDayString' => $selectedDayString ?? null,
            'shopcart' => $shopcart,
            'monthList' => $months,
            'dayList' => $dayList,
            'weeks' => $weeks,
            'preferredDeliveryWindowFrom' => $_SESSION['cart']['shipping']['preferredDeliveryWindow']['from'] ?? null,
            'preferredDeliveryWindowTo' => $_SESSION['cart']['shipping']['preferredDeliveryWindow']['to'] ?? null,
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param Post $post
     * @return Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function choiceOfDeliveryWindowAction(
        Shopcart $shopcart,
        \Frootbox\Http\Post $post,
    ): Response
    {
        if (!empty($this->getShopPlugin()->getConfig('shipping.times'))) {

            $times = $this->getShopPlugin()->getConfig('shipping.times');
            $selectedTime = $times[$post->get('PreferredDeliveryWindow')];

            $shopcart->setShippingCostsExtra('deliveryWindow', (float) $selectedTime['Surcharge'], 'Aufpreis für Lieferfenster ' . $selectedTime['TimeFrom'] . '–' . $selectedTime['TimeTo'] . ' Uhr');

            $_SESSION['cart']['shipping']['preferredDeliveryWindow']['from'] = $selectedTime['TimeFrom'];
            $_SESSION['cart']['shipping']['preferredDeliveryWindow']['to'] = $selectedTime['TimeTo'];
            $_SESSION['cart']['shipping']['preferredDeliveryWindow']['surcharge'] = $selectedTime['Surcharge'];
        }

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfDelivery'));
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart
     * @param \Frootbox\Http\Post $post
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupDay $selfPickupDayRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickUpTimeRepository
     * @return \Frootbox\View\Response
     * @throws \Frootbox\Exceptions\NotFound
     */
    public function choiceOfSelfPickupAction(
        Shopcart $shopcart,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupDay $selfPickupDayRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickUpTimeRepository,
    ): Response
    {
        if ($shopcart->getShipping('type') != 'selfPickup') {
            return new ResponseRedirect($this->getActionUri('choiceOfDelivery'));
        }

        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        if ($this->hasAttribute('Month')) {

            $xDate = new \DateTime($this->getAttribute('Month'));

            if ($this->hasAttribute('Shift')) {
                if ($this->getAttribute('Shift') == 'Prev') {
                    $xDate->modify('-1 month');
                }
                else {
                    $xDate->modify('+1 month');
                }
            }

            $now = new \DateTime();

            if ($xDate < $now) {
                $xDate = clone $now;
            }

            $now->modify('+6 month');

            if ($xDate > $now) {
                $xDate = clone $now;
            }

            $selectedMonth = $xDate->format('Y-m-d');
        }
        else {
            $selectedMonth = $post->get('month') ?? $shopcart->getShipping('deliveryDay');
        }

        $now = new \DateTime();

        if (!empty($selectedMonth)) {
            $date = new \DateTime($selectedMonth);
            $selectedMonthString = $date->format('Y-m');
            $selectedDayString = $date->format('Y-m-d');
        }
        else {
            $date = new \DateTime();
        }

        if ($date->format('Y-m') == $now->format('Y-m')) {
            $date->setDate($date->format('Y'), $date->format('m'), $now->format('d'));
        }
        else {
            $date->setDate($date->format('Y'), $date->format('m'), 1);
        }

        // Fetch self pickup times
        $pickUpTimes = $selfPickUpTimeRepository->fetch([
            'where' => [
                'pluginId' => $shopPlugin->getId(),
            ],
            'order' => [ 'dateStart ASC' ],
        ]);

        // Compute months
        $months = [];
        $monthDate = new \DateTime(date('Y-m') . '-01');

        for ($i = 1; $i <= 3; ++$i) {
            $months[] = clone $monthDate;

            $monthDate->modify('+1 month');
        }

        $firstRegularDay = new \DateTime();

        if (!empty($shopPlugin->getConfig('selfPickup.skipNextWorkdays'))) {

            $addedDays = $shopPlugin->getConfig('selfPickup.skipNextWorkdays');

            if ($firstRegularDay->format('N') == 6) {
                $addedDays += 2;
            }
            else if ($firstRegularDay->format('N') == 7) {
                $addedDays += 1;
            }

            $firstRegularDay->modify('+' . $addedDays . ' days');
        }

        $lastDate = new \DateTime($date->format('Y-m-d'));
        $lastDate->modify('+ ' . (($date->format('t') - $date->format('d')) + 1) . ' days');

        $period = new \DatePeriod(
            $date,
            new \DateInterval('P1D'),
            $lastDate,
        );

        $dayList = [];
        $dayCheck = [];

        foreach ($period as $key => $actDate) {

            $dateString = $actDate->format('Y-m-d');

            if ($firstRegularDay > $actDate) {
                continue;
            }

            $isBlocked = $selfPickupDayRepository->fetchOne([
                'where' => [
                    'dateStart' => $dateString . ' 00:00:00',
                ],
            ]);

            if ($isBlocked) {
                continue;
            }

            if (!empty($shopPlugin->getConfig('selfPickup.regularSelfPickupDays')) and !in_array($actDate->format('N'), $shopPlugin->getConfig('selfPickup.regularSelfPickupDays'))) {
                continue;
            }

            $available = false;

            foreach ($pickUpTimes as $pickUpTime) {

                if ($pickUpTime->isAvailableForDate($actDate)) {
                    $available = true;
                    break;
                }
            }

            if (!$available) {
                continue;
            }

            $dayList[] = $actDate;
            $dayCheck[$actDate->format('Y-m-d')] = true;
        }

        $weeks = [];

        $runningDate = clone $date;
        $runningDate->modify('first day of this month');

        $firstDayNum = $runningDate->format('N');

        $runningDate->modify('-' . ($firstDayNum - 1) . ' days');

        $checkMonth = $date->format('n');

        for ($w = 0; $w < 6; ++$w) {

            $week = [
                'nr' => (int) $runningDate->format('W'),
                'days' => [],
            ];

            for ($d = 1; $d <= 7; ++$d) {

                $day = [
                    'date' => $runningDate->format('Y-m-d'),
                    'isActive' => !empty($dayCheck[$runningDate->format('Y-m-d')]),
                ];

                $week['days'][] = $day;

                $runningDate->modify('+ 1 day');
            }

            $weeks[] = $week;

            if ($checkMonth != $runningDate->format('n')) {
                break;
            }
        }

        return new Response([
            'selectedMonth' => $selectedMonth,
            'selectedMonthString' => $selectedMonthString ?? null,
            'selectedDayString' => $selectedDayString ?? null,
            'shopcart' => $shopcart,
            'monthList' => $months,
            'dayList' => $dayList,
            'weeks' => $weeks,
        ]);
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickUpTimeRepository
     * @return Response
     * @throws \DateMalformedStringException
     */
    public function choiceOfSelfPickupTimeAction(
        Shopcart $shopcart,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\SelfPickupTime $selfPickUpTimeRepository,
    ): Response
    {
        // Obtain shop-plugin
        $shopPlugin = $this->getShopPlugin();

        // Fetch times
        $pickUpTimes = $selfPickUpTimeRepository->fetch([
            'where' => [
                'pluginId' => $shopPlugin->getId(),
            ],
            'order' => [ 'dateStart ASC' ],
        ]);

        $list = [];

        foreach ($pickUpTimes as $pickUpTime) {

            $xdate = new \DateTime($pickUpTime->getDateEnd());
            $date = new \DateTime($shopcart->getShipping('pickupDay'));
            $date->setTime($xdate->format('H'), $xdate->format('i'));

            if ($pickUpTime->isAvailableForDate($date)) {
                $list[] = $pickUpTime;
            }
        }

        // $selfPickupTimes = !empty($this->getConfig('SelfPickupTimes')) ? explode("\n", $this->getConfig('SelfPickupTimes')) : [];

        return new Response([
            'SelfPickupTimes' => $list,
        ]);
    }

    /**
     * @param \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
     * @return Response
     */
    public function continueShoppingAction(
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): Response
    {
        // Fetch shop plugin
        $plugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => 'Frootbox\\Ext\\Core\\ShopSystem\\Plugins\\ShopSystem\\Plugin',
            ],
        ]);

        return new \Frootbox\View\ResponseRedirect($plugin->getActionUri('index'));
    }

    /**
     * Display shopcart
     *
     * @param Shopcart $shopcart
     * @return Response
     */
    public function indexAction(
        Shopcart $shopcart,
    ): Response
    {
        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();

        return new Response([
            'shopcart' => $shopcart,
            'currencySign' => $shopPlugin->getCurrencySign(),
        ]);
    }

    /**
     * @param \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopCart
     * @return \Frootbox\View\Response
     */
    public function paymentAction(
        Shopcart $shopCart,
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        if ($shopCart->getItemCount() == 0) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        return new Response([
            'shopcart' => $shopCart,
        ]);
    }

    public function paymentFailedAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($this->getAttribute('bookingId'));

        d($booking);
    }

    /**
     * @param \Frootbox\Session $session
     * @return Response
     */
    public function loginAction(
        \Frootbox\Session $session
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        if ($session->isLoggedIn()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout'));
        }

        if (!empty($this->getConfig('skipCustomerLogin'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout'));
        }

        return new Response;
    }

    /**
     * @param Container $container
     * @param Config $config
     * @param Shopcart $shopcart
     * @return Response
     */
    public function preReviewAction(
        Container $container,
        \Frootbox\Config\Config $config,
        Shopcart $shopcart,
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        // Update payment method
        $paymentMethod = $shopcart->getPaymentMethod();

        if (method_exists($paymentMethod, 'postPaymentSelectionAction')) {

            $result = $container->call([ $paymentMethod, 'postPaymentSelectionAction' ], [
                'config' => $config,
                'shopcart' => $shopcart,
            ]);

            if (!empty($result['redirect'])) {
                return new \Frootbox\View\ResponseRedirect($result['redirect']);
            }
        }

        return new \Frootbox\View\ResponseRedirect($this->getActionUri('review'));
    }

    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Persistence\Repositories\Users $usersRepository
     * @return Response
     */
    public function reviewAction(
        Shopcart $shopcart,
        \Frootbox\TranslatorFactory $translatorFactory,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        if ($shopcart->getItemCount() == 0) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index', null, [ 'absolute' => true ]));
        }

        if (empty($shopcart->getPersonal('email'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout', null, [ 'absolute' => true ]));
        }

        if ($shopcart->getShipping('type') == 'selfPickup') {
            if (!empty($this->getShopConfig('choiceSelfPickupDay')) and empty($shopcart->getShipping('pickupDay'))) {
                return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfSelfPickup'));
            }
        }
        else {

            // Check for free choice of delivery day
            if (empty($shopcart->getShipping('deliveryDay'))) {
                foreach ($shopcart->getItems() as $item) {
                    if (!empty($item->getProduct()->getConfig('freeChoiceOfDeliveryDay'))) {
                        return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfDelivery'));
                    }
                }
            }
        }


        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();


        // Check shopcart
        foreach ($shopcart->getItems() as $item) {

            $product = $productsRepository->fetchById($item->getProductId());

            foreach ($product->getDatasheet()->getGroups() as $group) {

                if (empty($item->getFieldOption($group->getId()))) {

                    // Fetch product
                    $product = $productsRepository->fetchById($item->getProductId());

                    if (!empty($group->getOptionsForProduct($product)->getCount())) {
                        return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout', null, [ 'absolute' => true ]));
                    }
                }
            }
        }

        $method = $shopcart->getPaymentMethod();

        $summary = $method->renderSummary($view, $shopcart->getPaymentData());

        // Check customers email
        $domain = explode('@', $shopcart->getPersonal('email'))[1];
        $mailcheck = checkdnsrr($domain, 'MX');

        if (!IS_LOGGED_IN AND empty($this->getConfig('skipCustomerLogin'))) {

            $offerAccount = true;

            $result = $usersRepository->fetchOne([
                'where' => [
                    'email' => $shopcart->getPersonal('email'),
                ],
            ]);

            if ($result) {
                $offerAccount = false;
            }
        }
        else {
            $offerAccount = false;
        }

        return new Response([
            'paymentSummary'=> $summary,
            'shopcart' => $shopcart,
            'mailcheck' => $mailcheck,
            'offerAccount' => $offerAccount,
            'shopPlugin' => $shopPlugin,
            'currencySign' => $shopPlugin->getCurrencySign(),
        ]);
    }


    /**
     * @param Shopcart $shopcart
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\Persistence\Repositories\Users $usersRepository
     * @return Response
     */
    public function reviewFlowAction(
        Shopcart $shopcart,
        \Frootbox\TranslatorFactory $translatorFactory,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        if (!$this->isShopActive()) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index'));
        }

        if ($shopcart->getItemCount() == 0) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index', null, [ 'absolute' => true ]));
        }

        if (empty($shopcart->getPersonal('email'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout', null, [ 'absolute' => true ]));
        }

        if ($shopcart->getShipping('type') == 'selfPickup') {
            if (!empty($this->getShopConfig('choiceSelfPickupDay')) and empty($shopcart->getShipping('pickupDay'))) {
                return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfSelfPickup'));
            }
        }
        else {

            // Check for free choice of delivery day
            if (empty($shopcart->getShipping('deliveryDay'))) {
                foreach ($shopcart->getItems() as $item) {
                    if (!empty($item->getProduct()->getConfig('freeChoiceOfDeliveryDay'))) {
                        return new \Frootbox\View\ResponseRedirect($this->getActionUri('choiceOfDelivery'));
                    }
                }
            }
        }

        // Obtain shop plugin
        $shopPlugin = $this->getShopPlugin();


        // Check shopcart
        foreach ($shopcart->getItems() as $item) {

            $product = $productsRepository->fetchById($item->getProductId());

            foreach ($product->getDatasheet()->getGroups() as $group) {

                if (empty($item->getFieldOption($group->getId()))) {

                    // Fetch product
                    $product = $productsRepository->fetchById($item->getProductId());

                    if (!empty($group->getOptionsForProduct($product)->getCount())) {
                        return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout', null, [ 'absolute' => true ]));
                    }
                }
            }
        }

        $method = $shopcart->getPaymentMethod();

        $summary = $method->renderSummary($view, $shopcart->getPaymentData());

        // Check customers email
        $domain = explode('@', $shopcart->getPersonal('email'))[1];
        $mailcheck = checkdnsrr($domain, 'MX');

        if (!IS_LOGGED_IN AND empty($this->getConfig('skipCustomerLogin'))) {

            $offerAccount = true;

            $result = $usersRepository->fetchOne([
                'where' => [
                    'email' => $shopcart->getPersonal('email'),
                ],
            ]);

            if ($result) {
                $offerAccount = false;
            }
        }
        else {
            $offerAccount = false;
        }

        return new Response([
            'paymentSummary'=> $summary,
            'shopcart' => $shopcart,
            'mailcheck' => $mailcheck,
            'offerAccount' => $offerAccount,
            'shopPlugin' => $shopPlugin,
            'currencySign' => $shopPlugin->getCurrencySign(),
        ]);
    }
}
