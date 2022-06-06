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
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events
    ) {

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
    public function getPath ( ): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }

    /**
     *
     */
    public function ajaxSubmitBookingAction (
        \Frootbox\Http\Post $post,
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $events,
        \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Repositories\Bookings $bookings,
        \Frootbox\Db\Db $db,
        \Frootbox\TranslatorFactory $translationFactory,
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Payload $payload,
        \Frootbox\Builder $builder
    ): Response
    {
        // Validate required input
        $post->require([
            'eventId',
            'persons',
            'privacyPolicy',
            'payment',
        ]);

        // Fetch event
        $event = $events->fetchById($post->get('eventId'));

        // Check if event has free seats
        if ($event->getFreeSeats() <= 0) {
            throw new \Frootbox\Exceptions\RuntimeError('Event is fully booked.');
        }

        // Check if requested seats are available
        if ($event->getFreeSeats() < $post->get('persons')) {
            throw new \Frootbox\Exceptions\RuntimeError('Too many seats requested.');
        }

        // Begin database transaction
        $db->transactionStart();

        // Generate booking
        $booking = new \Frootbox\Ext\Core\Events\Plugins\Booking\Persistence\Booking([
            'pluginId' => $this->getId(),
            'parentId' => $event->getId(),
            'config' => [
                'persons' => $post->get('persons'),
                'owner' => $post->get('owner'),
                'payment' => $post->get('payment'),
            ],
        ]);

        // Create booking
        $booking = $bookings->insert($booking);

        // Update events booking state
        $bookedSeats = $event->getConfig('bookable.bookedSeats') ?? 0;
        $bookedSeats += $post->get('persons');

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
        $file = $builder->getFile($this->getConfig('mailTemplate'));

        $source = $view->render($file);

        // Init mailer
        // TODO: Wrap this into frootbox class

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        //Server settings
        $mail->isSMTP();
        $mail->Host = $config->get('mail.smtp.host');
        $mail->SMTPAuth = true;
        $mail->Username = $config->get('mail.smtp.username');
        $mail->Password = $config->get('mail.smtp.password');
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = "utf-8";
        $mail->Encoding = 'base64';
        $mail->SMTPOptions = array (
            'ssl' => array (
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ),
        );

        //Recipients
        $mail->setFrom($config->get('mail.defaults.from.address'), $config->get('mail.defaults.from.name'));
        $mail->addAddress($this->getConfig('recipient'));

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Buchung: ' . $event->getTitle();
        $mail->Body = $source;

        $mail->send();

        try {

            $payload->addData([
                'bookingId' => $booking->getId()
            ]);

            // Send mail to owner
            $mail->clearAddresses();
            $mail->addAddress($booking->getConfig('owner.email'));

            $mail->send();
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
        \Frootbox\Ext\Core\Events\Persistence\Repositories\Events $eventsRepository,
    ): Response
    {
        $event = null;

        if ($this->hasAttribute('eventId')) {
            $event = $eventsRepository->fetchById($this->getAttribute('eventId'));
        }

        return new Response([
            'event' => $event,
        ]);
    }
}
