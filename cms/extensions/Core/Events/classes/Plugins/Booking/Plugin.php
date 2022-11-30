<?php
/**
 *
 */

namespace Frootbox\Ext\Core\Events\Plugins\Booking;

use Frootbox\View\Response;

class Plugin extends \Frootbox\Persistence\AbstractPlugin
{
    use \Frootbox\Persistence\Traits\StandardUrls;

    protected $publicActions = [
        'index',
        'completed',
    ];

    /**
     *
     */
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function getBookingHistory (
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings
    ): \Frootbox\Db\Result
    {
        // Generate sql
        $sql = 'SELECT
            b.id,
            b.config,
            e.dateStart as eventDateStart,
            e.dateEnd as eventDateEnd
        FROM 
            assets b,
            assets e
        WHERE
            (
                b.pluginId = ' . $this->getId() . ' AND
                b.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Plugins\\\\Booking\\\\Persistence\\\\Booking"
            ) AND
            (
                e.id = b.parentId
            )
        ORDER BY
            e.dateStart ASC';

        // Fetch bookings
        $result = $bookings->fetchByQuery($sql);

        return $result;
    }

    /**
     *
     */
    public function getBookingHistoryByDay (
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings
    ): array
    {
        // Obtain history
        $history = $this->getBookingHistory($bookings);

        $list = [ ];

        foreach ($history as $booking) {

            $date = new \Frootbox\Dates\Date($booking->getEventDateStart());
            $key = $date->format('%Y-%m-%d');

            if (!isset($list[$key])) {

                $list[$key] = [
                    'date' => $date,
                    'bookings' => []
                ];
            }

            $list[$key]['bookings'][] = $booking;
        }

        return $list;
    }

    /**
     *
     */
    public function getEventsBookable(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
    ): array
    {
        if (empty($this->getConfig('source'))) {
            return [ ];
        }

        $sql = 'SELECT
            e.id,
            e.title,
            e.config,
            e.dateStart,
            e.dateEnd,
            
            l.id as location_id,
            l.title as location_title,
            l.street as location_street,
            l.streetNumber as location_streetNumber
        FROM
            assets e
        LEFT JOIN
            locations l
        ON
            e.parentId = l.id
        WHERE
            e.pluginId IN ("' . implode('", "', $this->getConfig('source')) . '") AND 
            e.visibility > 0 AND
            e.className = "Frootbox\\\\Ext\\\\Core\\\\Events\\\\Persistence\\\\Event" AND            
            (
                e.dateStart >= "' . date('Y-m-d H:i:s') . '" OR
                (
                    e.dateStart <= "' . date('Y-m-d H:i:s') . '" AND
                    e.dateEnd >= "' . date('Y-m-d H:i:s') . '"
                )
            )           
        ORDER BY
            e.dateStart ASC';

        // Fetch events
        $result = $events->fetchByQuery($sql);

        $list = [ ];

        foreach ($result as $event) {

            if (empty($event->getConfig('bookable.seats'))) {
                continue;
            }

            if (!empty($event->getConfig('bookable.bookedSeats')) and (int) $event->getConfig('bookable.bookedSeats') >= (int) $event->getConfig('bookable.seats')) {
                continue;
            }

            if (!empty($this->getConfig('closeEventAfterFirstBooking')) and !empty($event->getConfig('bookable.bookedSeats'))) {
                continue;
            }

            $list[] = $event;
        }

        return $list;
    }

    /**
     *
     */
    public function getPaymentMethods(
        \DI\Container $container,
    ): array
    {
        if (empty($this->getConfig('paymentmethods'))) {
            return [];
        }

        $methods = [];

        foreach ($this->getConfig('paymentmethods') as $method => $state) {

            $methodClass = '\\Frootbox\\Ext\\Core\\Events\\Plugins\\Booking\\PaymentMethods\\' . $method . '\\PaymentMethod';

            $methods[] = $container->get($methodClass);
        }

        return $methods;
    }

    /**
     *
     */
    public function getPersonsMin(): int
    {
        return !empty($this->getConfig('minPersons')) ? $this->getConfig('minPersons') : 1;
    }

    /**
     *
     */
    public function ajaxPrepareBookingAction(
        \DI\Container $container,
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Validate required input
        $post->require([
            'eventId',
            'persons',
            'payment',
        ]);

        // Fetch event
        $event = $eventRepository->fetchById($post->get('eventId'));

        if ($this->getConfig('alwaysBookCompleteEvent')) {
            $total = $event->getPrice();
            $persons = $event->getConfig('bookable.seats');
        }
        else {
            $total = $post->get('persons') * $event->getPrice();
            $persons = $post->get('persons');
        }

        $payment = $post->get('payment');

        if ($post->get('differentInvoiceRecipient')) {
            $payment['differentInvoiceRecipient'] = true;
            $payment['invoice'] = $post->get('invoice');
        }
        else {
            $payment['differentInvoiceRecipient'] = false;
            unset($payment['invoice']);
        }

        // Store booking
        $_SESSION['events']['booking'] = [
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'persons' => $persons,
                'price' => $event->getPrice(),
                'total' => $total,
            ],
            'personal' => $post->get('owner'),
            'payment' => $payment,
        ];

        // Get payment method
        $methodClass = '\\Frootbox\\Ext\\Core\\Events\\Plugins\\Booking\\PaymentMethods\\' . $payment['type'] . '\\PaymentMethod';
        $paymentMethod = $container->get($methodClass);

        if (method_exists($paymentMethod, 'postPaymentSelectionAction')) {
            $response = $paymentMethod->postPaymentSelectionAction();

            if (!empty($response['redirect'])) {
                die(json_encode([
                    'redirect' => $response['redirect'],
                ]));
            }
        }

        die(json_encode([
            'redirect' => $this->getActionUri('review'),
        ]));
    }

    /**
     *
     */
    public function ajaxSubmitBookingAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings,
        \Frootbox\Db\Db $db,
        \DI\Container $container,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\Builder $builder
    ): Response
    {
        // Validate required input
        $post->require([
            'privacyPolicy',
        ]);

        // Fetch event
        $event = $events->fetchById($_SESSION['events']['booking']['event']['id']);

        // Check if event has free seats
        if ($event->getFreeSeats() <= 0) {
            throw new \Frootbox\Exceptions\RuntimeError('Event is fully booked.');
        }

        // Check if requested seats are available
        if ($event->getFreeSeats() < $_SESSION['events']['booking']['event']['persons']) {
            throw new \Frootbox\Exceptions\RuntimeError('Too many seats requested.');
        }

        // Begin database transaction
        $db->transactionStart();

        // Get payment method
        $methodClass = '\\Frootbox\\Ext\\Core\\Events\\Plugins\\Booking\\PaymentMethods\\' . $_SESSION['events']['booking']['payment']['type'] . '\\PaymentMethod';
        $paymentMethod = $container->get($methodClass);

        // Generate booking
        $booking = new \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking([
            'pluginId' => $this->getId(),
            'parentId' => $event->getId(),
            'config' => [
                'persons' => $_SESSION['events']['booking']['event']['persons'],
                'owner' => $_SESSION['events']['booking']['personal'],
                'note' => $post->get('note'),
                'payment' => [
                    'state' => 'Created',
                    'type' => $_SESSION['events']['booking']['payment']['type'],
                ],
                'invoice' => !empty($_SESSION['events']['booking']['payment']['invoice']) ? $_SESSION['events']['booking']['payment']['invoice'] : null,
            ],
        ]);

        // Create booking
        $booking = $bookings->insert($booking);

        if (method_exists($paymentMethod, 'preCheckoutAction')) {
            $response = $paymentMethod->preCheckoutAction($booking);
        }

        // Update events booking state
        $bookedSeats = $event->getConfig('bookable.bookedSeats') ?? 0;
        $bookedSeats += $_SESSION['events']['booking']['event']['persons'];

        $event->addConfig([
            'bookable' => [
                'bookedSeats' => $bookedSeats,
            ],
        ]);

        $event->save();

        $db->transactionCommit();

        // Obtain translator
        $translator = $this->getTranslator($translationFactory);

        // Compose mail
        $view->set('booking', $booking);
        $view->set('event', $event);
        $view->set('plugin', $this);
        $view->set('translator', $translator);

        $builder->setPlugin($this)->setTemplate('Mail');
        $source = $builder->render($this->getConfig('mailTemplate'));

        // Init mailer
        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject('Buchung: ' . $event->getTitle());
        $mail->setBodyHtml($source);

        $mail->clearTo();
        $mail->addTo($this->getConfig('recipient'));

        $mailTransport->send($mail);

        try {
            $payload->addData([
                'bookingId' => $booking->getId()
            ]);

            $mail->clearTo();
            $mail->addTo($booking->getConfig('owner.email'));

            $mailTransport->send($mail);
        }
        catch ( \PHPMailer\PHPMailer\Exception $e ) {

            $payload->addData([
                'mailNotSent' => true
            ]);
        }

        die(json_encode([
            'redirect' => $this->getActionUri('completed', $payload->export())
        ]));
    }

    /**
     *
     */
    public function ajaxValidateIbanAction (
        \Frootbox\Http\Get $get
    ) {
        // Validate input using obeniban.com
        $url = 'https://openiban.com/validate/' . $get->get('iban') . '?getBIC=true&validateBankCode=true';
        $response = file_get_contents($url);

        header('Content-Type: application/json');

        die($response);
    }


    /**
     *
     */
    public function completedAction (
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings,
    ): Response
    {
        // Fetch booking
        $booking = $bookings->fetchById($this->getAttribute('bookingId'));

        return new Response([
            'booking' => $booking,
        ]);
    }

    /**
     *
     */
    public function indexAction(
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository,
    ): Response
    {

        $event = null;

        if ($this->hasAttribute('eventId')) {
            $event = $eventsRepository->fetchById($this->getAttribute('eventId'));
        }
        elseif ($eventId = $post->get('eventId')) {
            $event = $eventsRepository->fetchById($eventId);
        }

        return new Response([
            'event' => $event,
        ]);
    }

    /**
     *
     */
    public function reviewAction(
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventRepository,
    ): Response
    {
        // Fetch event
        $event = $eventRepository->fetchById($_SESSION['events']['booking']['event']['id']);

        // d($_SESSION['events']['booking']);

        return new Response([
            'event' => $event,
            'booking' => $_SESSION['events']['booking'],
        ]);
    }
}
