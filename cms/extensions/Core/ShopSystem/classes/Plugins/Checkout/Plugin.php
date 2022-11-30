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
        'review'
    ];

    /**
     * @param string $layout
     * @return string
     */
    public function getBaseActionView(string $layout): string
    {
        return $this->getPath() . 'Layouts/' . $layout . '/View.html.twig';
    }

    /**
     *
     */
    public function getNewsletterConnector(
        Container $container
    )
    {
        $newsletterConnector = $this->getShopConfig('newsletterConnector');

        if (empty($newsletterConnector)) {
            return null;
        }

        $newsletterConnector = $container->get($newsletterConnector);

        return $newsletterConnector;
    }

    /**
     *
     */
    public function getPaymentMethod(
        Container $container,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    )
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
    ): ?string
    {
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
    ): ?string
    {
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
    public function getPaymentXMethod(
        Container $container,
        // \Frootbox\TranslatorFactory $factory,
        // \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): array
    {
        return $this->getPaymentMethods($container);
    }

    /**
     *
     */
    public function getPaymentMethods(
        Container $container,
    ): array
    {
        $factory = $container->get(\Frootbox\TranslatorFactory::class);
        $contentElementsRepository = $container->get(\Frootbox\Persistence\Content\Repositories\ContentElements::class);

        // Generate translator
        $translator = $factory->get(GLOBAL_LANGUAGE);

        // Fetch shop system plugin
        $plugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => 'Frootbox\\Ext\\Core\\ShopSystem\\Plugins\\ShopSystem\\Plugin'
            ]
        ]);

        if (empty($plugin) or empty($plugin->getConfig('paymentmethods'))) {
            return [];
        }

        $paymentmethods = [];

        foreach ($plugin->getConfig('paymentmethods') as $paymentMethodClass) {

            $paymentMethod = new $paymentMethodClass;

            // Load language file
            $path = $paymentMethod->getPath() . 'resources/private/language/de-DE.php';
            $title = $paymentMethodClass;

            $scope = str_replace('\\', '.', substr(substr(get_class($paymentMethod), 0, -7), 13));
            $translator->setScope($scope);

            if (file_exists($path)) {
                $translator->addResource($path, $scope, false);
            }

            $paymentMethod->setTitle($translator->translate('Method.Title'));

            if (!empty($_SESSION['cart']['paymentmethod']['methodClass']) and $paymentMethodClass == '\\' . $_SESSION['cart']['paymentmethod']['methodClass']) {
                $paymentMethod->setActive();
            }

            $paymentmethods[] = $paymentMethod;
        }

        return $paymentmethods;
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
     *
     */
    public function getShopPlugin(): \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin
    {
        // Fetch shopsystem plugin
        $contentElementsRepository = $this->db->getModel(\Frootbox\Persistence\Content\Repositories\ContentElements::class);
        $shopPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Plugin::class
            ]
        ]);

        return $shopPlugin;
    }

    /**
     *
     */
    public function ajaxBankCheckAction(

    ): Response
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
    ): Response
    {
        $post->require([ 'username', 'password' ]);

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
     * @param Shopcart $shopcart
     * @param \Frootbox\Db\Db $dbms
     * @param Container $container
     * @param Post $post
     * @param \Frootbox\Session $session
     * @param Config $config
     * @param \Frootbox\View\Engines\Interfaces\Engine $view
     * @param \Frootbox\TranslatorFactory $translationFactory
     * @param \Frootbox\Persistence\Repositories\Users $usersRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\InputInvalid
     * @throws \Frootbox\Exceptions\InputMissing
     * @throws \Frootbox\Exceptions\RuntimeError
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function ajaxCheckoutAction(
        Shopcart $shopcart,
        \Frootbox\Db\Db $dbms,
        Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Session $session,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Coupons $couponsRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Stock $stockRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        // Validate required input
        $post->require([ 'privacyPolicy', 'rightOfWithdrawal' ]);

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

        // Fetch shopsystem plugin
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

            $sql = 'SELECT * FROM `shop_products_stocks` WHERE productId = ' . $item->getProductId() . ' AND JSON_CONTAINS(groupData, \'' . addslashes(json_encode($options)). '\');';


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

        // Perform additional payment action
        $paymentMethod = $shopcart->getPaymentMethod();

        if (method_exists($paymentMethod, 'preCheckoutAction')) {
            $paymentMethod->preCheckoutAction($shopcart);
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

        // Compose booking
        $booking = new \Frootbox\Ext\Core\ShopSystem\Persistence\Booking([
            'pluginId' => $this->getId(),
            'pageId' => $this->getPageId(),
            'title' => $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'),
            'config' => [
                'note' => $post->get('note'),
                'personal' => $shopcart->getPersonalData(),
                'shipping' => $shopcart->getShippingData(),
                'payment' => $shopcart->getPaymentInfo(),
                'products' => $shopcart->getItemsRaw(),
                'additionalinput' => $post->get('additionalinput'),
                'coupons' => $couponData,
                'persistedData' => [
                    'shippingCosts' => $shopcart->getShippingCosts(),
                    'taxSections' => $shopcart->getTaxSections(),
                ],
            ],
        ]);

        if ($session->isLoggedIn()) {
            $booking->setUserId($session->getUser()->getId());
        }

        if (!IS_LOGGED_IN and !empty($post->get('password'))) {

            $user = $usersRepository->insert(new \Frootbox\Persistence\User([
                'email' => $shopcart->getPersonal('email'),
                'type' => 'User',
            ]));

            $user->setPassword($post->get('password'));
            $user->save();

            $booking->setUserId($user->getId());
        }

        // Insert booking
        $booking = $bookingsRepository->insert($booking);

        // Generate order number
        $orderNumber = $shopPlugin->getConfig('orderNumberTemplate') ? $shopPlugin->getConfig('orderNumberTemplate') : '{R:100-999}-{R:A-Z}-{ID}';

        $orderNumber = preg_replace_callback('#\{R:([0-9]+)-([0-9]+)\}#', function ( $match ) {
            return rand($match[1], $match[2]);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:A-Z(:([0-9]+))?\}#', function ( $match ) {

            $length = !empty($match[2]) ? $match[2] : 1;

            $range = strtoupper(md5(microtime(true)));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = preg_replace_callback('#\{R:a-z(:([0-9]+))?\}#', function ( $match ) {

            $length = !empty($match[2]) ? $match[2] : 1;

            $range = md5(microtime(true));

            return substr($range, 0, $length);
        }, $orderNumber);

        $orderNumber = str_replace('{ID}', $booking->getId(), $orderNumber);

        $booking->addConfig([
            'orderNumber' => $orderNumber,
        ]);

        $booking->save();

        // Compose mails
        $view->set('translator', $translator);
        $view->set('shopcart', $shopcart);
        $view->set('booking', $booking);
        $view->set('orderNumber', $orderNumber);
        $view->set('serverpath', SERVER_PATH_PROTOCOL);

        if (!empty($shopPlugin->getConfig('textAbove'))) {

            $text = $shopPlugin->getConfig('textAbove');
            $text = str_replace('{name}', $shopcart->getPersonal('firstname') . ' ' . $shopcart->getPersonal('lastname'), $text);

            $view->set('textAbove', $text);
        }

        $view->set('textBelow', $shopPlugin->getConfig('textBelow'));

        preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

        $view->set('baseVendor', $match[1]);
        $view->set('baseExtension', $match[2]);


        $paymentInfoSave = $paymentMethod->renderSummarySave($view, $shopcart->getPaymentData());

        $view->set('paymentInfo', $paymentInfoSave);

        $file = $this->getPath() . 'resources/private/builder/Mail.html.twig';
        $sourceSave = $view->render($file);


        $file = $this->getPath() . 'resources/private/builder/ShopOwner.html.twig';

        $paymentInfo = $paymentMethod->renderSummary($view, $shopcart->getPaymentData());
        $view->set('paymentInfo', $paymentInfo);

        $source = $view->render($file);

        // Mark coupons redeemed
        foreach ($shopcart->getRedeemedCoupons() as $coupon) {

            if ($coupon->getValueLeft() > $coupon->getRedeemedValue()) {
                $coupon->setState('RedeemedPartially');
                $remaining = $coupon->getConfig('remaining') ?? $coupon->getValue();
                $coupon->addConfig([
                    'remaining' => ($remaining - $coupon->getRedeemedValue()),
                ]);
            }
            else {
                $coupon->setState('Redeemed');
            }

            $coupon->save();
        }

        $dbms->transactionCommit();

        // TODO change to general mail transport
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $config->get('mail.smtp.host');
        $mail->SMTPAuth = true;
        $mail->Username = $config->get('mail.smtp.username');
        $mail->Password = $config->get('mail.smtp.password');
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->CharSet = "utf-8";
        $mail->Encoding = 'base64';
        $mail->SMTPOptions = array (
            'ssl' => array (
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

        $mail->setFrom($config->get('mail.defaults.from.address'), $config->get('mail.defaults.from.name'));

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Shop-Bestellung';
        $mail->Body = $sourceSave;

        // Set addresses
        $mail->addAddress($shopcart->getPersonal('email'));
        $mail->addReplyTo($config->get('mail.defaults.from.address'));

        try {

            // Send customer
            $mail->send();

            $mailSent = true;
        }
            // Ignore exceptions from mail sending because its very likely a mistyped email
        catch ( \PHPMailer\PHPMailer\Exception $e ) {
            $mailSent = false;
        }

        $mail->clearAddresses();
        $mail->clearReplyTos();

        // Send moderator
        if (!empty($shopPlugin->getConfig('recipients'))) {
            $recipients = explode(',', $shopPlugin->getConfig('recipients'));
        }
        else {
            $recipients = [ $config->get('mail.defaults.from.address') ];
        }

        $mail->Body = $source;

        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }

        $mail->addReplyTo($shopcart->getPersonal('email'));
        $mail->send();

        unset($_SESSION['cart']);

        return new \Frootbox\View\ResponseJson([
            'bookingId' => $booking->getId(),
            'mailSent' => $mailSent,
            'success' => 'Die Buchung wurde erfolgreich abgeschlossen',
            'continue' => $this->getActionUri('complete', [ 'bookingId' => $booking->getId(), 'mailSent' => $mailSent, ]),
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
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [ 'plugin' => $this, 'shopcart' => $shopcart, 'editable' => false ]),
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
    ): Response
    {
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
        }
        catch ( \Exception $e ) {
            die($e->getMessage());
        }


        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [ 'plugin' => $this, 'shopcart' => $shopcart, 'editable' => false ])
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
    ): Response
    {
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
            }
            elseif (!empty($surcharge)) {

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
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [ 'plugin' => $this, 'shopcart' => $shopcart, 'editable' => true ]),
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
    ): Response
    {
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
     * @param Container $container
     * @param Post $post
     * @param Config $config
     * @param Shopcart $shopcart
     * @param \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository
     * @return Response
     * @throws \Frootbox\Exceptions\InputMissing
     */
    public function ajaxSubmitDataAction(
        Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Config\Config $config,
        Shopcart $shopcart,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        $required = [
            'personal.firstname',
            'personal.lastname',
            'personal.street',
            'personal.streetNumber',
            'personal.postalCode',
            'personal.city',
            'personal.gender',
            'payment.method',
        ];

        $shipping = $post->get('shipping');

        if ($shipping['type'] == 'shipToAddress') {
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

        // Set data
        $shopcart->setPersonal($post->get('personal'));
        $shopcart->setShipping($post->get('shipping'));

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

        if (!empty($config->get('Ext.Core.ShopSystem.CartFilter.PostCheckout'))) {

            foreach ($config->get('Ext.Core.ShopSystem.CartFilter.PostCheckout') as $filterClass) {

                $filter = new $filterClass($shopcart, $this);
                $container->call([ $filter, 'run' ]);
            }
        }

        // Update payment method
        $shopcart->setPaymentMethodClass($post->get('payment')['method']);

        $paymentMethod = $shopcart->getPaymentMethod();

        if (method_exists($paymentMethod, 'postPaymentSelectionAction')) {

            $result = $container->call([ $paymentMethod, 'postPaymentSelectionAction' ], [
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

        $shopcart->setItem($item);

        $partialsViewhelper->setParameters([
            'plugin' => $this,
        ]);

        return new \Frootbox\View\ResponseJson([
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [ 'plugin' => $this, 'shopcart' => $shopcart, 'editable' => true ]),
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
            'html' => $partialsViewhelper->renderPartial('ItemsTable', [ 'plugin' => $this, 'shopcart' => $shopcart, 'editable' => true ]),
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
        // fetch booking
        $booking = $bookingsRepository->fetchById($this->getAttribute('bookingId'));

        return new Response([
            'booking' => $booking,
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
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Addresses $addressesRepository,
    ): Response
    {
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

        return new Response([
            'user' => $user ?? null,
            'shopcart' => $shopcart,
            'addresses' => $addresses ?? [],
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
        Shopcart $shopcart
    ): Response
    {
        // Generate response
        return new Response([
            'shopcart' => $shopcart
        ]);
    }

    /**
     * @param \Frootbox\Session $session
     * @return Response
     */
    public function loginAction(
        \Frootbox\Session $session
    ): Response
    {
        if ($session->isLoggedIn()) {
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
        Container               $container,
        \Frootbox\Config\Config $config,
        Shopcart                $shopcart,
    ): Response
    {
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
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Persistence\Repositories\Users $usersRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
    ): Response
    {
        if ($shopcart->getItemCount() == 0) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('index', null, [ 'absolute' => true ]));
        }

        if (empty($shopcart->getPersonal('email'))) {
            return new \Frootbox\View\ResponseRedirect($this->getActionUri('checkout', null, [ 'absolute' => true ]));
        }

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

        if (!IS_LOGGED_IN) {

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
        ]);
    }
}
