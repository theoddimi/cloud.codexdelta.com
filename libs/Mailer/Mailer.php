<?php

namespace Codexdelta\Libs\Mailer;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
    public function __construct(private PHPMailer $PHPMailer, private array $recipients = [])
    {
        $PHPMailer->SMTPDebug = SMTP::DEBUG_OFF;       //Enable verbose debug output
        $PHPMailer->isSMTP();                             //Send using SMTP
        $PHPMailer->Host = $_ENV["SMTP_SERVER"];    //Set the SMTP server to send through
        $PHPMailer->SMTPAuth = true;                    //Enable SMTP authentication
        $PHPMailer->Username = $_ENV["SMTP_USERNAME"];    //SMTP username
        $PHPMailer->Password = $_ENV["SMTP_PASSWORD"];    //SMTP password
        $PHPMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
        $PHPMailer->Port = $_ENV["SMTP_PORT"];
        $PHPMailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $PHPMailer->addAddress(env('DEFAULT_MAIL_NOTIFICATION_RECIPIENT'));
        $this->addRecipients();
    }

    /**
     * @return self
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public static function wakeupAndSetup(): self
    {
        $mailer = new self(new PHPMailer(true));
        $mailer->setUpWithDefaultHeaders();

        return $mailer;
    }

    /**
     * @return void
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function setUpWithDefaultHeaders(): void
    {
        //Recipients
        $this->PHPMailer->setFrom('bot@codexdelta.com', "CODEXDELTA");
        $this->PHPMailer->addReplyTo('no-reply@codexdelta.com', 'NO REPLY');

        //Content
        // $this->PHPMailer->isHTML(true);                                  //Set email format to HTML
        // $this->PHPMailer->AltBody = 'This is the body in plain text for non-HTML mail clients';
    }

    private function addRecipients()
    {
        foreach ($this->recipients as $recipient) {
            $this->PHPMailer->addAddress($recipient['address'], $recipient['name']);
        }
    }

    public function send(string $subject, string $message)
    {
        try {
            $this->PHPMailer->Subject = "CLOUD CODEXDELTA | " . $subject;
            $this->PHPMailer->Body    = $message;

            $this->PHPMailer->send();
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->PHPMailer->ErrorInfo}" . " | " . $e->getMessage());
        }
    }
}
