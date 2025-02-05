<?php

namespace Codexdelta\App\Clients\Notifications;

use Codexdelta\Libs\Mailer\Mailer;
use PHPMailer\PHPMailer\Exception;

class MailerClient extends NotificationClient
{
    private Mailer $mailer;

    /**
     * @throws Exception
     */
    public function setup(): self
    {
        $this->mailer = Mailer::wakeupAndSetup();

        return $this;
    }

    public function send(string $subject, string $message): void
    {
        $this->mailer->send($subject, $message);
    }
}