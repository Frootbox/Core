<?php
/**
 *
 */

namespace Frootbox\Ext\Core\ShopSystem\Plugins\ShopSystem\Admin\Bookings;

use Frootbox\Admin\Controller\Response;
use Frootbox\Session;

class Controller extends \Frootbox\Admin\AbstractPluginController
{
    /**
     *
     */
    public function getPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    public function ajaxBookingTransferAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Ext\Core\ShopSystem\Integrations\Delegator $delegator,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        $delegator->transferBooking($booking);

        return self::getResponse('json', 200, [
            'success' => 'Die Buchung wurde  übertragen.',
        ]);
    }

    /**
     *
     */
    public function ajaxCancelAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        $booking->setState('Cancelled');
        $booking->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     *
     */
    public function ajaxCreateTestAction(
        \Frootbox\Session $session,
        \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Shopcart $shopcart,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Products $productsRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository
    ): Response
    {
        // Fetch random product
        $product = $productsRepository->fetchOne([
            'order' => [ 'rand()' ]
        ]);

        $shopcart->addItem($product);

        // Fetch checkout plugin
        $checkoutPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class
            ]
        ]);

        $url = 'https://randomuser.me/api/';
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        $user = $result['results'][0];


        $personal = [
            'gender' => ucfirst($user['gender']),
            'firstname' => $user['name']['first'],
            'lastname' => $user['name']['last'],
            'street' => $user['location']['street']['name'],
            'streetNumber' => $user['location']['street']['number'],
            'city' => $user['location']['city'],
            'postalCode' => $user['location']['postcode'],
            'email' => $session->getUser()->getEmail(),
            'phone' => $user['phone'],
        ];

        $shopcart->setPersonal($personal);

        return self::getResponse('json', 200, [
            'redirect' => ($checkoutPlugin ? $checkoutPlugin->getUri(null, null, [ 'absolute' => true ]) : null)
        ]);
    }

    /**
     *
     */
    public function ajaxEmailResendModAction(
        \Frootbox\Http\Get $get,
        \Frootbox\Config\Config $config,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\Persistence\Content\Repositories\ContentElements $pluginRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        // Obtaincheckout plugin
        $checkoutPlugin = $pluginRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class,
            ],
        ]);

        // Obtain translator
        $translator = $checkoutPlugin->getTranslator($translationFactory);

        // Compose mails
        $view->set('translator', $translator);
        $view->set('booking', $booking);
        $view->set('serverpath', SERVER_PATH_PROTOCOL);
        $view->set('currencySign', $this->plugin->getCurrencySign());

        if (!empty($this->plugin->getConfig('textAbove'))) {
            $text = $this->plugin->getConfig('textAbove');
            $text = str_replace(
                '{name}',
                $booking->getConfig('personal.firstname') . ' ' . $booking->getConfig('personal.lastname'),
                $text
            );

            $view->set('textAbove', $text);
        }

        $view->set('textBelow', $this->plugin->getConfig('textBelow'));

        preg_match('#\/([a-zA-Z0-9]+)\/([a-zA-Z0-9]+)\/pages\/$#', $config->get('pageRootFolder'), $match);

        $view->set('baseVendor', $match[1]);
        $view->set('baseExtension', $match[2]);


        $paymentMethod = $booking->getPaymentMethod();

        $paymentInfoSave = $paymentMethod->renderSummarySave($view, $booking->getPaymentData());

        $view->set('paymentInfo', $paymentInfoSave);
        $view->set('netPrices', $checkoutPlugin->getConfig('showNetPrices'));

        $file = $checkoutPlugin->getPath() . 'resources/private/builder/Mail.html.twig';
        $sourceSave = $view->render($file);

        $paymentInfo = $paymentMethod->renderSummary($view, $booking->getPaymentData());
        $view->set('paymentInfo', $paymentInfo);

        $source = $view->render($file);

        // Compose mails
        $subject = !empty($this->plugin->getConfig('subject')) ? $this->plugin->getConfig('subject') : 'Shop-Bestellung';
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

        /*
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
        */

        $mail->clearTo();
        $mail->clearReplyTo();

        // Send moderator
        if (!empty($this->plugin->getConfig('recipients'))) {
            $recipients = explode(',', $this->plugin->getConfig('recipients'));
        } else {
            $recipients = [ $config->get('mail.defaults.from.address') ];
        }

        $mail->setSubject('KOPIE: ' . $mail->getSubject());
        $mail->setBodyHtml($source);

        foreach ($recipients as $email) {
            $mail->addTo($email);
        }


        $mail->setReplyTo($booking->getConfig('personal.email'));
        $mailTransport->send($mail);

        return self::getResponse('json', 200, [
            'success' => 'Die Mail wurde gesendet.',
        ]);
    }

    /**
     *
     */
    public function ajaxReSubmitAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        $booking->setState('Booked');
        $booking->save();

        return self::getResponse('json', 200, [
            'success' => 'Die Daten wurden gespeichert.',
        ]);
    }

    /**
     *
     */
    public function detailsAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        $method = $booking->getPaymentMethod();

        $summary = $method->renderSummary($view, $booking->getConfig('payment.data') ?? []);

        return self::getResponse('html', 200, [
            'paymentMethod' => $method,
            'summary' => $summary,
            'booking' => $booking
        ]);
    }

    /**
     *
     */
    public function downloadInvoiceAction(
        \Frootbox\Http\Get $get,
        \Frootbox\View\Builder $builder,
        \Frootbox\Persistence\Repositories\Files $fileRepository,
        \Frootbox\Ext\Core\ShopSystem\Persistence\Repositories\Bookings $bookingsRepository,
        \Frootbox\Persistence\Content\Repositories\ContentElements $contentElementsRepository,
    ): Response
    {
        // Fetch booking
        $booking = $bookingsRepository->fetchById($get->get('bookingId'));

        // Obtain checkout plugin
        $checkoutPlugin = $contentElementsRepository->fetchOne([
            'where' => [
                'className' => \Frootbox\Ext\Core\ShopSystem\Plugins\Checkout\Plugin::class,
            ],
        ]);

        // Render source
        $builder->setBaseFile($checkoutPlugin->getPath() . 'resources/private/builder/Invoice.html.twig');


        // Fetch background file
        $file = $fileRepository->fetchByUid($this->plugin->getUid('invoice-footer'));

        $pdfSource = $builder->render([
            'plugin' => $checkoutPlugin,
            'shopPlugin' => $this->plugin,
            'booking' => $booking,
            'background' => ($file ? FILES_DIR . $file->getPath() : null),
            'currencySign' => $this->plugin->getCurrencySign(),
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
            dest: 'D'
        );
    }

    /**
     *
     */
    public function indexAction(): Response
    {
        return self::getResponse();
    }
}
