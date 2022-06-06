<?php
/**
 *
 */

namespace Frootbox;

class Mailer
{
    private $recipients = [];
    private $subject;
    private $bodyHtml;

    private $view;
    private $config;
    private $mailtransport;

    /**
     *
     */
    public function __construct(
        \Frootbox\View\Engines\Interfaces\Engine $view,
        \Frootbox\Config\Config $config,
        \Frootbox\Mail\Transports\Interfaces\TransportInterface $mailTransport
    )
    {
        $this->view = $view;
        $this->config = $config;
        $this->mailtransport = $mailTransport;
    }

    /**
     * Add address "to"
     */
    public function addRecipient($recipient)
    {
        if (!is_array($recipient)) {
            $recipient = [
                'email' => $recipient
            ];
        }

        $this->recipients[] = $recipient;
    }

    /**
     *
     */
    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    /**
     *
     */
    public function getSubject(): string
    {
        return $this->subject ?? 'kein Betreff';
    }

    /**
     *
     */
    public function renderBody(
        string $viewFile,
        array $parameters = null
    ): void
    {

        foreach($parameters as $key => $value) {
            $this->view->set($key, $value);
        }

        // Render source
        $this->bodyHtml = $this->view->render($viewFile);
    }

    /**
     *
     */
    public function send(): void
    {
        $mail = new \Frootbox\Mail\Envelope;
        $mail->setSubject($this->getSubject());
        $mail->setBodyHtml($this->getBodyHtml());

        $mail->clearTo();

        foreach ($this->recipients as $recipient) {
            $mail->addTo($recipient['email']);
        }

        $this->mailtransport->send($mail);
    }

    /**
     *
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }
}
