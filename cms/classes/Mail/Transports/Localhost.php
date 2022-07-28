<?php
/**
 *
 */

namespace Frootbox\Mail\Transports;

class Localhost extends AbstractTransport
{
    protected $mailer = null;

    /**
     *
     */
    public function send(\Frootbox\Mail\Envelope $envelope): void
    {
        if ($this->mailer === null) {

            $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);

            //Server settings
            #$this->mailer->isSMTP();
            #$this->mailer->SMTPKeepAlive = true;
            #$this->mailer->Host = $this->config->get('mail.smtp.host');
            #$this->mailer->SMTPAuth = true;
            #$this->mailer->Username = $this->config->get('mail.smtp.username');
            #$this->mailer->Password = $this->config->get('mail.smtp.password');
            #$this->mailer->SMTPSecure = 'ssl';
            #$this->mailer->Port = 465;
            $this->mailer->CharSet = "utf-8";
            $this->mailer->Encoding = 'base64';

            ini_set('sendmail_from', $this->config->get('mail.defaults.from.address'));

            $this->mailer->setFrom($this->config->get('mail.defaults.from.address'), $this->config->get('mail.defaults.from.name'), false);

            // Debug mode is optional
            if (!empty($this->config->get('mail.smtp.debug'))) {
                // $this->mailer->SMTPDebug  = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
                $this->mailer->SMTPDebug  = \PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;
            }

            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $envelope->getSubject();
        }

        if (!empty($envelope->getReplyTo())) {
            $this->mailer->AddReplyTo($envelope->getReplyTo()->getAddress());
        }
        else {
            $this->mailer->clearReplyTos();
        }

        $this->mailer->Body = $envelope->getBodyHtml();

        $this->mailer->clearAddresses();

        foreach ($envelope->getRecipients() as $recipient) {
            $this->mailer->addAddress($recipient->getAddress(), $recipient->getName());
        }

        foreach ($envelope->getAttachments() as $attachment) {
            $this->mailer->addAttachment($attachment->getPath(), $attachment->getName());
        }

        $this->mailer->send();
/*
        try {

            if (!$this->mailer->send()) {
                echo "FEHLER!";
                print_r(error_get_last());
                exit;
            }

        }
        catch ( \Exception $e ) {
            echo "FEHLER!";
            p($e->getMessage());
            p(error_get_last());
            exit;
        }
*/
    }
}
